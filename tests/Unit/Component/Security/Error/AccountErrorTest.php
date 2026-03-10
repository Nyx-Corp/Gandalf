<?php

declare(strict_types=1);

namespace Gandalf\Tests\Unit\Component\Security\Error;

use Cortex\Component\Exception\DomainException;
use Gandalf\Component\Security\Error\AccountError;
use Gandalf\Component\Security\Error\AccountException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Gandalf\Component\Security\Error\AccountError
 * @covers \Gandalf\Component\Security\Error\AccountException
 */
class AccountErrorTest extends TestCase
{
    // =======================================================================
    // ENUM CASES
    // =======================================================================

    public function testEnumCasesExist(): void
    {
        $cases = AccountError::cases();

        $this->assertCount(5, $cases);
        $this->assertContains(AccountError::InvalidUsername, $cases);
        $this->assertContains(AccountError::InvalidPassword, $cases);
        $this->assertContains(AccountError::UsernameAlreadyExists, $cases);
        $this->assertContains(AccountError::InvalidToken, $cases);
        $this->assertContains(AccountError::TokenExpired, $cases);
    }

    public function testEnumValues(): void
    {
        $this->assertSame('account.error.invalid_username', AccountError::InvalidUsername->value);
        $this->assertSame('account.error.invalid_password', AccountError::InvalidPassword->value);
        $this->assertSame('account.error.username_already_exists', AccountError::UsernameAlreadyExists->value);
        $this->assertSame('account.error.invalid_token', AccountError::InvalidToken->value);
        $this->assertSame('account.error.token_expired', AccountError::TokenExpired->value);
    }

    // =======================================================================
    // EXCEPTION WRAPPING
    // =======================================================================

    public function testAccountExceptionWrapsError(): void
    {
        $exception = new AccountException(AccountError::InvalidUsername);

        $this->assertSame(AccountError::InvalidUsername, $exception->error);
        $this->assertSame('account.error.invalid_username', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testAccountExceptionImplementsDomainException(): void
    {
        $exception = new AccountException(AccountError::InvalidToken);

        $this->assertInstanceOf(DomainException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testAccountExceptionGetDomainReturnsSecurity(): void
    {
        $exception = new AccountException(AccountError::InvalidPassword);

        $this->assertSame('security', $exception->getDomain());
    }

    public function testAccountExceptionPreservesPreviousException(): void
    {
        $previous = new \InvalidArgumentException('original error');
        $exception = new AccountException(AccountError::TokenExpired, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testAccountExceptionMessageMatchesErrorValue(): void
    {
        foreach (AccountError::cases() as $error) {
            $exception = new AccountException($error);
            $this->assertSame($error->value, $exception->getMessage());
        }
    }
}
