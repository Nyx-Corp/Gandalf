<?php

namespace Gandalf\Component\Security\Action\AskPasswordReset;

use Cortex\Component\Action\ActionHandler;
use Gandalf\Component\Security\Factory\AccountFactory;
use Gandalf\Component\Security\Factory\TokenFactory;
use Gandalf\Component\Security\Hasher\TokenHasher;
use Gandalf\Component\Security\Persistence\TokenStore;
use Symfony\Component\Clock\ClockInterface;

class Handler implements ActionHandler
{
    public function __construct(
        private readonly AccountFactory $accountFactory,
        private readonly TokenFactory $tokenFactory,
        private readonly TokenStore $tokenStore,
        private readonly TokenHasher $tokenHasher,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(Command $command): Response
    {
        $account = $this->accountFactory->query()
            ->filter(username: $command->email)
            ->getCollection()
            ->first();

        if (!$account) {
            // Never reveal whether the email exists
            return new Response(null, null);
        }

        $tokenData = $this->tokenHasher->generate();

        $token = $this->tokenFactory->create()
            ->with(account: $account)
            ->with(intention: 'reset_password')
            ->with(tokenHash: $tokenData['tokenHash'])
            ->with(scopes: [])
            ->with(expiresAt: $this->clock->now()->modify('+1 hour'))
            ->with(createdAt: $this->clock->now())
            ->build();

        $this->tokenStore->sync($token);

        return new Response($token, rawToken: $tokenData['token']);
    }
}
