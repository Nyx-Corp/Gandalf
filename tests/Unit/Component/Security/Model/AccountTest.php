<?php

declare(strict_types=1);

namespace Gandalf\Tests\Unit\Component\Security\Model;

use Cortex\ValueObject\Email;
use Cortex\ValueObject\HashedPassword;
use Gandalf\Component\Security\Model\Account;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * @covers \Gandalf\Component\Security\Model\Account
 */
class AccountTest extends TestCase
{
    // =======================================================================
    // __toString() TESTS
    // =======================================================================

    public function testToStringReturnsEmailString(): void
    {
        $account = new Account(new Email('admin@bridgeit-app.test'));

        $this->assertSame('admin@bridgeit-app.test', (string) $account);
    }

    // =======================================================================
    // CONSTRUCTOR TESTS
    // =======================================================================

    public function testConstructorWithMinimalArgs(): void
    {
        $email = new Email('user@example.com');
        $account = new Account($email);

        $this->assertSame($email, $account->username);
        $this->assertNull($account->password);
        $this->assertSame([], $account->acl);
        $this->assertInstanceOf(Uuid::class, $account->uuid);
    }

    public function testConstructorWithFullArgs(): void
    {
        $email = new Email('user@example.com');
        $password = new HashedPassword('$2y$13$hashed');
        $uuid = Uuid::v7();

        $account = new Account(
            username: $email,
            password: $password,
            acl: ['ROLE_ADMIN', 'ROLE_USER'],
            uuid: $uuid,
        );

        $this->assertSame($email, $account->username);
        $this->assertSame($password, $account->password);
        $this->assertSame(['ROLE_ADMIN', 'ROLE_USER'], $account->acl);
        $this->assertSame($uuid, $account->uuid);
    }

    // =======================================================================
    // ACL TESTS
    // =======================================================================

    public function testAclCastsStringableObjectsToStrings(): void
    {
        $role = new class implements \Stringable {
            public function __toString(): string
            {
                return 'ROLE_CUSTOM';
            }
        };

        $account = new Account(
            username: new Email('user@example.com'),
            acl: [$role],
        );

        $this->assertSame(['ROLE_CUSTOM'], $account->acl);
        $this->assertContainsOnly('string', $account->acl);
    }

    public function testAclWithStringValues(): void
    {
        $account = new Account(
            username: new Email('user@example.com'),
            acl: ['ROLE_ADMIN', 'ROLE_MANAGER'],
        );

        $this->assertSame(['ROLE_ADMIN', 'ROLE_MANAGER'], $account->acl);
    }

    public function testAclEmptyByDefault(): void
    {
        $account = new Account(new Email('user@example.com'));

        $this->assertSame([], $account->acl);
        $this->assertIsArray($account->acl);
    }

    public function testAclContainsOnlyStrings(): void
    {
        $account = new Account(
            username: new Email('user@example.com'),
            acl: ['ROLE_ADMIN', 'ROLE_USER', 'ROLE_MANAGER'],
        );

        $this->assertCount(3, $account->acl);
        $this->assertContainsOnly('string', $account->acl);
    }

    public function testAclIsImmutableAfterConstruction(): void
    {
        $acl = ['ROLE_ADMIN'];
        $account = new Account(
            username: new Email('user@example.com'),
            acl: $acl,
        );

        // The original array mutation should not affect the account
        $acl[] = 'ROLE_USER';

        $this->assertSame(['ROLE_ADMIN'], $account->acl);
    }
}
