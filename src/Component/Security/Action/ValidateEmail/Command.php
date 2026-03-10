<?php

namespace Gandalf\Component\Security\Action\ValidateEmail;

use Gandalf\Component\Security\Model\Token;

class Command
{
    public function __construct(
        public readonly Token $token,
        public readonly string $rawToken,
    ) {
    }
}
