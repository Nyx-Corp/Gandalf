<?php

declare(strict_types=1);

namespace Gandalf\Component\Security\Representation;

use Cortex\Component\Mapper\ArrayMapper;
use Cortex\Component\Mapper\DefaultPublicGroupsTrait;
use Cortex\Component\Mapper\ModelRepresentation;
use Cortex\Component\Mapper\Relation;
use Cortex\Component\Mapper\Strategy;
use Cortex\Component\Mapper\Value;
use Cortex\Component\Model\Attribute\Model;
use Gandalf\Component\Security\Model\Token;
use Symfony\Component\Uid\Uuid;

#[Model(Token::class)]
class TokenRepresentation implements ModelRepresentation
{
    use DefaultPublicGroupsTrait;

    public function writer(string $group = 'default'): ArrayMapper
    {
        return match ($group) {
            'store' => new ArrayMapper([
                'account' => Relation::toUuid('account_uuid'),
                'expiresAt' => Value::Date,
                'createdAt' => Value::Date,
                'scopes' => Value::Json,
            ]),
            default => new ArrayMapper(
                mapping: [
                    'tokenHash' => Value::Ignore,
                    'expiresAt' => Value::Date,
                    'createdAt' => Value::Date,
                    'scopes' => Value::Json,
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
                    'account_uuid' => Relation::toModel('account'),
                    'expires_at' => Value::Date,
                    'created_at' => Value::Date,
                    'scopes' => Value::Json,
                ],
                format: Strategy::AutoMapCamel,
            ),
            default => new ArrayMapper(
                mapping: [
                    'uuid' => fn (string $v) => new Uuid($v),
                    'expiresAt' => Value::Date,
                    'createdAt' => Value::Date,
                    'scopes' => Value::Json,
                ],
                format: Strategy::AutoMapCamel,
            ),
        };
    }

    public function groups(): array
    {
        return [
            'store' => ['uuid', 'account', 'intention', 'tokenHash', 'expiresAt', 'label', 'scopes', 'createdAt'],
            'id' => ['uuid'],
            'list' => ['uuid', 'intention', 'label', 'expiresAt', 'scopes', 'account@id'],
        ];
    }
}
