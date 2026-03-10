<?php

namespace Gandalf\Component\Security\Hasher;

use Cortex\ValueObject\HashedPassword;

interface PasswordHasherInterface
{
    public function hashPassword(
        #[\SensitiveParameter]
        string $plainPassword,
    ): HashedPassword;
}
