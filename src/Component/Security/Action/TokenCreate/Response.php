<?php

namespace Gandalf\Component\Security\Action\TokenCreate;

use Gandalf\Component\Security\Model\Token;

class Response
{
    public function __construct(
        public readonly Token $token,
        public readonly string $rawToken,
    ) {
    }
}
