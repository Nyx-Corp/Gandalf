<?php

declare(strict_types=1);

namespace Gandalf\Bridge\Doctrine;

use Cortex\Bridge\Doctrine\DbalBridge;
use Cortex\Bridge\Doctrine\DbalMappingConfiguration;
use Cortex\Bridge\Doctrine\DbalModelAdapterTrait;
use Cortex\Bridge\Symfony\Model\Attribute\Middleware;
use Cortex\Component\Model\ModelMiddleware;
use Cortex\Component\Model\Scope;
use Gandalf\Component\Security\Model\Account;
use Gandalf\Component\Security\Representation\AccountRepresentation;

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

    public function __construct(DbalBridge $dbalBridge, AccountRepresentation $representation)
    {
        $this->dbal = $dbalBridge->createAdapter(new DbalMappingConfiguration(
            table: 'security_account',
            primaryKey: 'uuid',
            modelToTableMapper: $representation->writer('store'),
            tableToModelMapper: $representation->reader('store'),
            modelClass: Account::class,
        ));
    }
}
