<?php

declare(strict_types=1);

namespace Gandalf\Component\Security\Representation;

use Cortex\Component\Mapper\ArrayMapper;
use Cortex\Component\Mapper\DefaultPublicGroupsTrait;
use Cortex\Component\Mapper\ModelRepresentation;
use Cortex\Component\Mapper\Strategy;
use Cortex\Component\Mapper\Value;
use Cortex\Component\Model\Attribute\Model;
use Cortex\ValueObject\Email;
use Cortex\ValueObject\HashedPassword;
use Gandalf\Component\Security\Model\Account;
use Symfony\Component\Uid\Uuid;

#[Model(Account::class)]
class AccountRepresentation implements ModelRepresentation
{
    use DefaultPublicGroupsTrait;

    public function writer(string $group = 'default'): ArrayMapper
    {
        return match ($group) {
            'store' => new ArrayMapper([
                'username' => fn (Email $email) => (string) $email,
                'password' => fn (?HashedPassword $p) => $p ? (string) $p : null,
                'acl' => Value::Json,
            ]),
            default => new ArrayMapper(
                mapping: [
                    'password' => Value::Ignore,
                    'acl' => Value::Json,
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
                    'username' => fn (string $email) => new Email($email),
                    'password' => fn (?string $p) => $p ? new HashedPassword($p) : null,
                    'archived_at' => Value::Date,
                    'acl' => Value::Json,
                ],
                format: Strategy::AutoMapCamel,
            ),
            default => new ArrayMapper(
                mapping: [
                    'uuid' => fn (string $v) => new Uuid($v),
                    'username' => fn (string $email) => new Email($email),
                ],
                format: Strategy::AutoMapCamel,
            ),
        };
    }

    public function groups(): array
    {
        return [
            'store' => ['uuid', 'username', 'password', 'acl', 'archivedAt'],
            'id' => ['uuid'],
            'list' => ['uuid', 'username', 'acl'],
        ];
    }
}
