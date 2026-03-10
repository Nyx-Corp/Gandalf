<?php

namespace Gandalf\Component\Security\Action\AccountCreate;

use Gandalf\Component\Security\Model\Account;

class Response
{
    public function __construct(
        public readonly Account $account,
    ) {
    }
}
