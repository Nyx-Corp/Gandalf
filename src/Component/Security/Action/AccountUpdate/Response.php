<?php

namespace Gandalf\Component\Security\Action\AccountUpdate;

use Gandalf\Component\Security\Model\Account;

class Response
{
    public function __construct(
        public readonly Account $account,
    ) {
    }
}
