<?php

namespace Gandalf\Component\Security\Action\ValidateEmail;

use Gandalf\Component\Security\Model\Account;

class Response
{
    public function __construct(
        public readonly Account $account,
    ) {
    }
}
