<?php

namespace Gandalf\Component\Security\Action\CreatePassword;

use Gandalf\Component\Security\Model\Token;

class Command
{
    public function __construct(
        public readonly Token $token,
        public readonly string $rawToken,
        #[\SensitiveParameter]
        public readonly string $plainPassword,
    ) {
    }
}
