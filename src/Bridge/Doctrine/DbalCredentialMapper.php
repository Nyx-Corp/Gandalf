<?php

declare(strict_types=1);

namespace Gandalf\Bridge\Doctrine;

use Cortex\Bridge\Doctrine\DbalBridge;
use Cortex\Bridge\Doctrine\DbalMappingConfiguration;
use Cortex\Bridge\Doctrine\DbalModelAdapterTrait;
use Cortex\Bridge\Symfony\Model\Attribute\Middleware;
use Cortex\Component\Model\ModelMiddleware;
use Cortex\Component\Model\Scope;
use Gandalf\Component\Security\Model\Credential;
use Gandalf\Component\Security\Representation\CredentialRepresentation;

#[Middleware(Credential::class, on: Scope::All, handler: 'onDbal', priority: 2)]
class DbalCredentialMapper implements ModelMiddleware
{
    use DbalModelAdapterTrait;

    public function __construct(DbalBridge $dbalBridge, CredentialRepresentation $representation)
    {
        $this->dbal = $dbalBridge->createAdapter(new DbalMappingConfiguration(
            table: 'security_credential',
            primaryKey: 'uuid',
            modelToTableMapper: $representation->writer('store'),
            tableToModelMapper: $representation->reader('store'),
            modelClass: Credential::class,
        ));
    }
}
