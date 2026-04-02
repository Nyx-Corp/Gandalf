<?php

declare(strict_types=1);

namespace Gandalf\Component\Security\Model;

use Cortex\Component\Model\Archivable;
use Cortex\Component\Model\Uuidentifiable;
use Gandalf\Component\Security\Enum\ProviderType;
use Symfony\Component\Uid\Uuid;

class Credential implements \Stringable
{
    use Uuidentifiable;
    use Archivable;

    public function __construct(
        public readonly string $name,
        public readonly ProviderType $provider,
        public readonly string $data = '',
        public readonly ?\DateTimeInterface $expiresAt = null,
        public readonly ?string $refreshToken = null,
        ?Uuid $uuid = null,
    ) {
        $this->uuid = $uuid;
    }

    public function isExpired(): bool
    {
        if (null === $this->expiresAt) {
            return false;
        }

        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
