<?php

declare(strict_types=1);

namespace Gandalf\Component\Security\Action\CredentialArchive;

use Gandalf\Component\Security\Model\Credential;

class Command
{
    public function __construct(
        public readonly Credential $credential,
        public readonly bool $isArchived = true,
    ) {
    }
}
