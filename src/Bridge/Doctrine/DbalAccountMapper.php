<?php

declare(strict_types=1);

namespace Gandalf\Bridge\Doctrine;

use Cortex\Bridge\Doctrine\DbalBridge;
use Cortex\Bridge\Doctrine\DbalMappingConfiguration;
use Cortex\Bridge\Doctrine\DbalModelAdapterTrait;
use Cortex\Bridge\Symfony\Model\Attribute\Middleware;
use Cortex\Component\Mapper\ArrayMapper;
use Cortex\Component\Mapper\Strategy;
use Cortex\Component\Mapper\Value;
use Cortex\Component\Model\ModelMiddleware;
use Cortex\Component\Model\Scope;
use Cortex\ValueObject\Email;
use Cortex\ValueObject\HashedPassword;
use Gandalf\Component\Security\Model\Account;
use Symfony\Component\Uid\Uuid;

/**
 * Maps the `security_account` table to the Gandalf Account model.
 *
 * Default table structure: uuid, username, password, acl (JSON), archived_at.
 * Projects can extend or replace this mapper if their schema differs.
 */
#[Middleware(Account::class, on: Scope::All, handler: 'onDbal', priority: 2)]
class DbalAccountMapper implements ModelMiddleware
{
    use DbalModelAdapterTrait;

    public function __construct(DbalBridge $dbalBridge)
    {
        $this->dbal = $dbalBridge->createAdapter(new DbalMappingConfiguration(
            table: 'security_account',
            primaryKey: 'uuid',
            modelToTableMapper: new ArrayMapper([
                'username' => fn (Email $email) => (string) $email,
                'password' => fn (?HashedPassword $p) => $p ? (string) $p : null,
                'acl' => Value::Json,
            ]),
            tableToModelMapper: new ArrayMapper(
                mapping: [
                    'uuid' => fn (string $uuid) => new Uuid($uuid),
                    'username' => fn (string $email) => new Email($email),
                    'password' => fn (?string $p) => $p ? new HashedPassword($p) : null,
                    'archived_at' => Value::Date,
                    'acl' => Value::Json,
                ],
                format: Strategy::AutoMapCamel,
            ),
            modelClass: Account::class,
        ));
    }
}
