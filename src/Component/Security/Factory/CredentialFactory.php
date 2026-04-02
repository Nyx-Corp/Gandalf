<?php

declare(strict_types=1);

namespace Gandalf\Component\Security\Factory;

use Cortex\Component\Model\Attribute\Model;
use Cortex\Component\Model\Factory\ModelFactory;
use Gandalf\Component\Security\Model\Credential;

#[Model(Credential::class)]
class CredentialFactory extends ModelFactory
{
}
