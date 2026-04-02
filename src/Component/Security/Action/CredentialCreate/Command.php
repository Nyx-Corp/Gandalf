<?php

declare(strict_types=1);

namespace Gandalf\Component\Security\Action\CredentialCreate;

use Gandalf\Component\Security\Enum\ProviderType;

class Command
{
    public function __construct(
        public readonly string $name,
        public readonly ProviderType $provider,
        public readonly array $rawData = [],
        public readonly ?\DateTimeInterface $expiresAt = null,
        public readonly ?string $rawRefreshToken = null,
    ) {
    }
}
