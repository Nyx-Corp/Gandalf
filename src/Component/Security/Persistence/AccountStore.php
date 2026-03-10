<?php

namespace Gandalf\Component\Security\Persistence;

use Cortex\Component\Model\Attribute\Model;
use Cortex\Component\Model\Store\ModelStore;
use Gandalf\Component\Security\Model\Account;

#[Model(Account::class)]
class AccountStore extends ModelStore
{
}
