<?php

declare(strict_types=1);

namespace Gandalf\Component\Security\Action\CredentialArchive;

use Cortex\Component\Action\ActionHandler;
use Gandalf\Component\Security\Persistence\CredentialStore;
use Symfony\Component\Clock\ClockInterface;

class Handler implements ActionHandler
{
    public function __construct(
        private readonly ClockInterface $clock,
        private readonly CredentialStore $store,
    ) {
    }

    public function __invoke(Command $command): Response
    {
        $credential = $command->credential;

        if ($command->isArchived) {
            $credential->archive($this->clock->now());
        } else {
            $credential->restore();
        }

        $this->store->sync($credential);

        return new Response($credential, $credential->isArchived() === $command->isArchived);
    }
}
