<?php

namespace Gandalf\Component\Security\Action\AccountArchive;

use Gandalf\Component\Security\Model\Account;

class Response
{
    public function __construct(
        public readonly Account $account,
        public readonly bool $isSuccess = true,
    ) {
    }
}
