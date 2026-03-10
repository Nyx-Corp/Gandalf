<?php

namespace Gandalf\Component\Security\Factory;

use Cortex\Component\Model\Attribute\Model;
use Cortex\Component\Model\Factory\ModelFactory;
use Gandalf\Component\Security\Model\Account;

#[Model(Account::class)]
class AccountFactory extends ModelFactory
{
}
