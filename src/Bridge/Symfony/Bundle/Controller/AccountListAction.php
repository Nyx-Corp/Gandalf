<?php

declare(strict_types=1);

namespace Gandalf\Bridge\Symfony\Bundle\Controller;

use Cortex\Bridge\Symfony\Controller\ControllerInterface;
use Gandalf\Component\Security\Factory\AccountFactory;
use Gandalf\Component\Security\Model\AccountCollection;

class AccountListAction implements ControllerInterface
{
    public function __construct(
        private readonly AccountFactory $accountFactory,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(AccountCollection $accounts): array
    {
        $query = $accounts->query;

        $query->decorate(
            sortables: ['username'],
            archivable: true,
        );

        return [
            'collection' => $accounts->toArray(),
            'form' => $query->getDecorator(),
            'pager' => $query->pager,
        ];
    }
}
