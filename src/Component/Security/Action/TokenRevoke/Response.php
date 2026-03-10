<?php

namespace Gandalf\Component\Security\Action\TokenRevoke;

use Gandalf\Component\Security\Model\Token;

class Response
{
    public function __construct(
        public readonly Token $token,
    ) {
    }
}
