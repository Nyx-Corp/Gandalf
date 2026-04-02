<?php

declare(strict_types=1);

namespace Gandalf\Bridge\Doctrine;

use Cortex\Bridge\Doctrine\DbalBridge;
use Cortex\Bridge\Doctrine\DbalMappingConfiguration;
use Cortex\Bridge\Doctrine\DbalModelAdapterTrait;
use Cortex\Bridge\Doctrine\JoinDefinition;
use Cortex\Bridge\Symfony\Model\Attribute\Middleware;
use Cortex\Component\Model\ModelMiddleware;
use Cortex\Component\Model\Scope;
use Gandalf\Component\Security\Factory\AccountFactory;
use Gandalf\Component\Security\Model\Token;
use Gandalf\Component\Security\Representation\TokenRepresentation;

/**
 * Maps the `security_token` table to the Gandalf Token model.
 *
 * Default table structure: uuid, account_uuid, intention, token_hash,
 * expires_at, label, scopes (JSON), created_at.
 * Projects can extend or replace this mapper if their schema differs.
 */
#[Middleware(Token::class, on: Scope::All, handler: 'onDbal', priority: 2)]
class DbalTokenMapper implements ModelMiddleware
{
    use DbalModelAdapterTrait;

    public function __construct(
        DbalBridge $dbalBridge,
        TokenRepresentation $representation,
        private readonly AccountFactory $accountFactory,
        private readonly DbalAccountMapper $accountMapper,
    ) {
        $this->dbal = $dbalBridge->createAdapter(new DbalMappingConfiguration(
            table: 'security_token',
            primaryKey: 'uuid',
            joins: [
                'account' => new JoinDefinition(
                    factory: $this->accountFactory,
                    joinConfig: $this->accountMapper->getConfiguration(),
                ),
            ],
            modelToTableMapper: $representation->writer('store'),
            tableToModelMapper: $representation->reader('store'),
            modelClass: Token::class,
        ));
    }
}
