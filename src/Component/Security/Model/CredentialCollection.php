<?php

declare(strict_types=1);

namespace Gandalf\Component\Security\Model;

use Cortex\Component\Model\ModelCollection;
use Cortex\ValueObject\RegisteredClass;

/**
 * @extends ModelCollection<Credential>
 */
class CredentialCollection extends ModelCollection
{
    protected static function expectedType(): ?RegisteredClass
    {
        return new RegisteredClass(Credential::class);
    }
}
