<?php

declare(strict_types=1);

namespace Gandalf\Tests\Unit\Bridge\Symfony\Security;

use Cortex\Bridge\Symfony\Serializer\RepresentationRegistry;
use Cortex\Component\Mapper\ArrayMapper;
use Cortex\Component\Mapper\DefaultPublicGroupsTrait;
use Cortex\Component\Mapper\ModelRepresentation;
use Gandalf\Bridge\Symfony\Security\RepresentationGroupVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchy;

/**
 * @covers \Gandalf\Bridge\Symfony\Security\RepresentationGroupVoter
 */
class RepresentationGroupVoterTest extends TestCase
{
    private RepresentationRegistry $registry;
    private RoleHierarchy $roleHierarchy;
    private RepresentationGroupVoter $voter;

    protected function setUp(): void
    {
        $this->registry = new RepresentationRegistry();
        $this->registry->register(FakeModel::class, new FakeRepresentation());

        $this->roleHierarchy = new RoleHierarchy([
            'ROLE_USER' => ['*@detail'],
            'ROLE_EDITOR' => ['ROLE_USER', 'fakemodel@full'],
            'ROLE_ADMIN' => ['ROLE_EDITOR', '*@full'],
        ]);

        $this->voter = new RepresentationGroupVoter($this->roleHierarchy, $this->registry);
    }

    // =======================================================================
    // PUBLIC GROUPS
    // =======================================================================

    public function testPublicGroupAlwaysGranted(): void
    {
        $token = $this->tokenWithRoles([]);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, [FakeModel::class, 'id'], ['REPRESENTATION_VIEW']),
        );
    }

    public function testListIsPublic(): void
    {
        $token = $this->tokenWithRoles([]);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, [FakeModel::class, 'list'], ['REPRESENTATION_VIEW']),
        );
    }

    // =======================================================================
    // EXACT SCOPE MATCH
    // =======================================================================

    public function testDetailGrantedForRoleUser(): void
    {
        $token = $this->tokenWithRoles(['ROLE_USER']);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, [FakeModel::class, 'detail'], ['REPRESENTATION_VIEW']),
        );
    }

    public function testDetailDeniedWithoutRole(): void
    {
        $token = $this->tokenWithRoles([]);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, [FakeModel::class, 'detail'], ['REPRESENTATION_VIEW']),
        );
    }

    // =======================================================================
    // WILDCARD
    // =======================================================================

    public function testWildcardFullGrantedForAdmin(): void
    {
        $token = $this->tokenWithRoles(['ROLE_ADMIN']);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, [FakeModel::class, 'full'], ['REPRESENTATION_VIEW']),
        );
    }

    public function testWildcardDetailGrantedForUser(): void
    {
        // ROLE_USER has *@detail → any model's detail group
        $token = $this->tokenWithRoles(['ROLE_USER']);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, [FakeModel::class, 'detail'], ['REPRESENTATION_VIEW']),
        );
    }

    public function testFullDeniedForUser(): void
    {
        $token = $this->tokenWithRoles(['ROLE_USER']);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, [FakeModel::class, 'full'], ['REPRESENTATION_VIEW']),
        );
    }

    // =======================================================================
    // HIERARCHY INHERITANCE
    // =======================================================================

    public function testEditorInheritsUserScopes(): void
    {
        // ROLE_EDITOR inherits ROLE_USER (*@detail) + fakemodel@full
        $token = $this->tokenWithRoles(['ROLE_EDITOR']);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, [FakeModel::class, 'detail'], ['REPRESENTATION_VIEW']),
        );
        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, [FakeModel::class, 'full'], ['REPRESENTATION_VIEW']),
        );
    }

    // =======================================================================
    // UNSUPPORTED ATTRIBUTES
    // =======================================================================

    public function testAbstainsOnOtherAttributes(): void
    {
        $token = $this->tokenWithRoles(['ROLE_ADMIN']);

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, [FakeModel::class, 'full'], ['SOME_OTHER_ATTRIBUTE']),
        );
    }

    // =======================================================================
    // HELPERS
    // =======================================================================

    private function tokenWithRoles(array $roles): UsernamePasswordToken
    {
        return new UsernamePasswordToken(new FakeUser($roles), 'main', $roles);
    }
}

// =======================================================================
// TEST FIXTURES
// =======================================================================

class FakeModel
{
    public function __construct(public readonly string $uuid = 'fake-1') {}
}

class FakeRepresentation implements ModelRepresentation
{
    use DefaultPublicGroupsTrait;

    public function writer(string $group = 'default'): ArrayMapper
    {
        return new ArrayMapper();
    }

    public function reader(string $group = 'default'): ArrayMapper
    {
        return new ArrayMapper();
    }

    public function groups(): array
    {
        return [
            'store' => ['uuid'],
            'id' => ['uuid'],
            'list' => ['uuid'],
            'detail' => ['uuid'],
            'full' => ['uuid'],
        ];
    }
}

class FakeUser implements \Symfony\Component\Security\Core\User\UserInterface
{
    public function __construct(private readonly array $roles) {}

    public function getRoles(): array { return $this->roles; }

    public function eraseCredentials(): void {}

    public function getUserIdentifier(): string { return 'fake'; }
}
