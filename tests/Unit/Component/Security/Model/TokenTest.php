<?php

declare(strict_types=1);

namespace Gandalf\Tests\Unit\Component\Security\Model;

use Cortex\ValueObject\Email;
use Gandalf\Component\Security\Model\Account;
use Gandalf\Component\Security\Model\Token;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Token::class)]
class TokenTest extends TestCase
{
    private Account $account;

    protected function setUp(): void
    {
        $this->account = new Account(new Email('test@example.com'));
    }

    // =======================================================================
    // isExpired() TESTS
    // =======================================================================

    public function testIsExpiredReturnsFalseForFutureDate(): void
    {
        $token = new Token(
            account: $this->account,
            intention: 'api',
            tokenHash: hash('sha256', 'test-token'),
            expiresAt: new \DateTimeImmutable('+1 hour'),
        );

        $this->assertFalse($token->isExpired());
    }

    public function testIsExpiredReturnsTrueForPastDate(): void
    {
        $token = new Token(
            account: $this->account,
            intention: 'api',
            tokenHash: hash('sha256', 'test-token'),
            expiresAt: new \DateTimeImmutable('-1 hour'),
        );

        $this->assertTrue($token->isExpired());
    }

    public function testIsExpiredReturnsTrueForDistantPast(): void
    {
        $token = new Token(
            account: $this->account,
            intention: 'api',
            tokenHash: hash('sha256', 'test-token'),
            expiresAt: new \DateTimeImmutable('2020-01-01'),
        );

        $this->assertTrue($token->isExpired());
    }

    public function testIsExpiredReturnsFalseForDistantFuture(): void
    {
        $token = new Token(
            account: $this->account,
            intention: 'api',
            tokenHash: hash('sha256', 'test-token'),
            expiresAt: new \DateTimeImmutable('2099-12-31'),
        );

        $this->assertFalse($token->isExpired());
    }

    // =======================================================================
    // matchesScope() TESTS
    // =======================================================================

    public function testMatchesScopeReturnsTrueWhenScopesAreNull(): void
    {
        $token = new Token(
            account: $this->account,
            intention: 'api',
            tokenHash: hash('sha256', 'test-token'),
            expiresAt: new \DateTimeImmutable('+1 hour'),
            scopes: null,
        );

        $this->assertTrue($token->matchesScope('/any/path'));
        $this->assertTrue($token->matchesScope('/another/path'));
    }

    public function testMatchesScopeReturnsTrueForMatchingPattern(): void
    {
        $token = new Token(
            account: $this->account,
            intention: 'api',
            tokenHash: hash('sha256', 'test-token'),
            expiresAt: new \DateTimeImmutable('+1 hour'),
            scopes: ['/api/v1/club/*'],
        );

        $this->assertTrue($token->matchesScope('/api/v1/club/members'));
    }

    public function testMatchesScopeReturnsFalseForNonMatchingPattern(): void
    {
        $token = new Token(
            account: $this->account,
            intention: 'api',
            tokenHash: hash('sha256', 'test-token'),
            expiresAt: new \DateTimeImmutable('+1 hour'),
            scopes: ['/api/v1/club/*'],
        );

        $this->assertFalse($token->matchesScope('/api/v1/admin/settings'));
    }

    public function testMatchesScopeReturnsTrueWhenOneOfMultipleScopesMatches(): void
    {
        $token = new Token(
            account: $this->account,
            intention: 'api',
            tokenHash: hash('sha256', 'test-token'),
            expiresAt: new \DateTimeImmutable('+1 hour'),
            scopes: ['/api/v1/club/*', '/api/v1/tournament/*'],
        );

        $this->assertTrue($token->matchesScope('/api/v1/tournament/create'));
    }

    public function testMatchesScopeReturnsFalseWhenNoScopeMatches(): void
    {
        $token = new Token(
            account: $this->account,
            intention: 'api',
            tokenHash: hash('sha256', 'test-token'),
            expiresAt: new \DateTimeImmutable('+1 hour'),
            scopes: ['/api/v1/club/*', '/api/v1/tournament/*'],
        );

        $this->assertFalse($token->matchesScope('/api/v1/admin/settings'));
    }

    public function testMatchesScopeReturnsFalseForEmptyScopesArray(): void
    {
        $token = new Token(
            account: $this->account,
            intention: 'api',
            tokenHash: hash('sha256', 'test-token'),
            expiresAt: new \DateTimeImmutable('+1 hour'),
            scopes: [],
        );

        $this->assertFalse($token->matchesScope('/any/path'));
    }

    public function testMatchesScopeWithExactMatch(): void
    {
        $token = new Token(
            account: $this->account,
            intention: 'api',
            tokenHash: hash('sha256', 'test-token'),
            expiresAt: new \DateTimeImmutable('+1 hour'),
            scopes: ['/api/v1/club/members'],
        );

        $this->assertTrue($token->matchesScope('/api/v1/club/members'));
        $this->assertFalse($token->matchesScope('/api/v1/club/members/extra'));
    }

    // =======================================================================
    // CONSTRUCTOR TESTS
    // =======================================================================

    public function testConstructorSetsAllProperties(): void
    {
        $expiresAt = new \DateTimeImmutable('+1 hour');
        $createdAt = new \DateTimeImmutable();

        $token = new Token(
            account: $this->account,
            intention: 'password_reset',
            tokenHash: 'abc123',
            expiresAt: $expiresAt,
            label: 'My API Key',
            scopes: ['/api/*'],
            createdAt: $createdAt,
        );

        $this->assertSame($this->account, $token->account);
        $this->assertSame('password_reset', $token->intention);
        $this->assertSame('abc123', $token->tokenHash);
        $this->assertSame($expiresAt, $token->expiresAt);
        $this->assertSame('My API Key', $token->label);
        $this->assertSame(['/api/*'], $token->scopes);
        $this->assertSame($createdAt, $token->createdAt);
    }

    public function testConstructorDefaultsOptionalProperties(): void
    {
        $token = new Token(
            account: $this->account,
            intention: 'api',
            tokenHash: 'hash',
            expiresAt: new \DateTimeImmutable('+1 hour'),
        );

        $this->assertNull($token->label);
        $this->assertNull($token->scopes);
        $this->assertNull($token->createdAt);
    }
}
