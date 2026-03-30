<?php

namespace Gandalf\Component\Security\Action\AccountArchive;

use Cortex\Component\Action\ActionHandler;
use Gandalf\Component\Security\Persistence\AccountStore;
use Symfony\Component\Clock\ClockInterface;

class Handler implements ActionHandler
{
    public function __construct(
        private readonly ClockInterface $clock,
        private readonly AccountStore $store,
    ) {
    }

    public function __invoke(Command $command): Response
    {
        $account = $command->account;

        if ($command->isArchived) {
            $account->archive($this->clock->now());
        } else {
            $account->restore();
        }

        $this->store->sync($account);

        return new Response($account, $account->isArchived() === $command->isArchived);
    }
}
