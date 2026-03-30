<?php

namespace Gandalf\Component\Security\Action\TokenCreate;

use Cortex\Component\Action\ActionHandler;
use Gandalf\Component\Security\Factory\TokenFactory;
use Gandalf\Component\Security\Hasher\TokenHasher;
use Gandalf\Component\Security\Persistence\TokenStore;
use Symfony\Component\Clock\ClockInterface;

class Handler implements ActionHandler
{
    public function __construct(
        private readonly TokenFactory $factory,
        private readonly TokenStore $store,
        private readonly TokenHasher $tokenHasher,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(Command $command): Response
    {
        $tokenData = $this->tokenHasher->generate();

        $token = $this->factory->create()
            ->with(account: $command->account)
            ->with(intention: $command->intention)
            ->with(tokenHash: $tokenData['tokenHash'])
            ->with(expiresAt: $this->clock->now()->modify($command->expiresIn))
            ->with(label: $command->label)
            ->with(scopes: $command->scopes)
            ->with(createdAt: $this->clock->now())
            ->build();

        $this->store->sync($token);

        return new Response($token, rawToken: $tokenData['token']);
    }
}
