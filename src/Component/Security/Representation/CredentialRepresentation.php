<?php

declare(strict_types=1);

namespace Gandalf\Component\Security\Representation;

use Cortex\Component\Mapper\ArrayMapper;
use Cortex\Component\Mapper\DefaultPublicGroupsTrait;
use Cortex\Component\Mapper\ModelRepresentation;
use Cortex\Component\Mapper\Strategy;
use Cortex\Component\Mapper\Value;
use Cortex\Component\Model\Attribute\Model;
use Gandalf\Component\Security\Enum\ProviderType;
use Gandalf\Component\Security\Model\Credential;
use Symfony\Component\Uid\Uuid;

#[Model(Credential::class)]
class CredentialRepresentation implements ModelRepresentation
{
    use DefaultPublicGroupsTrait;

    public function writer(string $group = 'default'): ArrayMapper
    {
        return match ($group) {
            'store' => new ArrayMapper([
                'name' => 'name',
                'provider' => ProviderType::class,
                'data' => 'data',
                'expiresAt' => Value::Date,
                'refreshToken' => 'refresh_token',
                'archivedAt' => Value::Date,
            ]),
            default => new ArrayMapper(
                mapping: [
                    'provider' => ProviderType::class,
                    'data' => Value::Ignore,
                    'refreshToken' => Value::Ignore,
                    'expiresAt' => Value::Date,
                    'archivedAt' => Value::Date,
                ],
                format: Strategy::AutoMapCamel,
            ),
        };
    }

    public function reader(string $group = 'default'): ArrayMapper
    {
        return match ($group) {
            'store' => new ArrayMapper(
                mapping: [
                    'uuid' => fn (string $uuid) => new Uuid($uuid),
                    'provider' => ProviderType::class,
                    'expiresAt' => Value::Date,
                    'archivedAt' => Value::Date,
                ],
                format: Strategy::AutoMapCamel,
            ),
            default => new ArrayMapper(
                mapping: [
                    'uuid' => fn (string $v) => new Uuid($v),
                    'provider' => ProviderType::class,
                ],
                format: Strategy::AutoMapCamel,
            ),
        };
    }

    public function groups(): array
    {
        return [
            'store' => ['uuid', 'name', 'provider', 'data', 'expiresAt', 'refreshToken', 'archivedAt'],
            'id' => ['uuid'],
            'list' => ['uuid', 'name', 'provider', 'expiresAt'],
        ];
    }
}
