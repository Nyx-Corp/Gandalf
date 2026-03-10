<?php

namespace Gandalf\Component\Security\Action\AccountArchive;

use Gandalf\Component\Security\Model\Account;

class Command
{
    public function __construct(
        public readonly Account $account,
        public readonly bool $isArchived = true,
    ) {
    }
}
