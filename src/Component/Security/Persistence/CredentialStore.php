<?php

declare(strict_types=1);

namespace Gandalf\Component\Security\Persistence;

use Cortex\Component\Model\Attribute\Model;
use Cortex\Component\Model\Store\ModelStore;
use Gandalf\Component\Security\Model\Credential;

#[Model(Credential::class)]
class CredentialStore extends ModelStore
{
}
