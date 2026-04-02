<?php

declare(strict_types=1);

namespace Gandalf\Component\Security\Action\CredentialEdit;

use Gandalf\Component\Security\Enum\ProviderType;
use Symfony\Component\Uid\Uuid;

class Command
{
    public function __construct(
        public readonly Uuid $uuid,
        public readonly ?string $name = null,
        public readonly ?ProviderType $provider = null,
        public readonly ?array $rawData = null,
        public readonly ?\DateTimeInterface $expiresAt = null,
        public readonly ?string $rawRefreshToken = null,
    ) {
    }
}
