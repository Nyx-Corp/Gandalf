<?php

declare(strict_types=1);

namespace Gandalf\Bridge\Symfony\Bundle\Controller;

use Cortex\Bridge\Symfony\Controller\ControllerInterface;
use Gandalf\Component\Security\Model\CredentialCollection;

class CredentialListAction implements ControllerInterface
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(CredentialCollection $credentials): array
    {
        $query = $credentials->query;

        $query->decorate(
            sortables: ['name', 'provider'],
            archivable: true,
        );

        return [
            'collection' => $credentials->toArray(),
            'form' => $query->getDecorator(),
            'pager' => $query->pager,
        ];
    }
}
