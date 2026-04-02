<?php

declare(strict_types=1);

namespace Gandalf\Component\Security\Action\CredentialArchive;

use Cortex\Component\Action\ActionHandler;
use Cortex\Component\Date\DateTimeFactory;
use Gandalf\Component\Security\Persistence\CredentialStore;

class Handler implements ActionHandler
{
    public function __construct(
        private readonly DateTimeFactory $dateTimeFactory,
        private readonly CredentialStore $store,
    ) {
    }

    public function __invoke(Command $command): Response
    {
        $credential = $command->credential;

        if ($command->isArchived) {
            $credential->archive($this->dateTimeFactory->now());
        } else {
            $credential->restore();
        }

        $this->store->sync($credential);

        return new Response($credential, $credential->isArchived() === $command->isArchived);
    }
}
