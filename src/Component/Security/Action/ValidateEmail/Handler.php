<?php

namespace Gandalf\Component\Security\Action\ValidateEmail;

use Cortex\Component\Action\ActionHandler;
use Gandalf\Component\Security\Error\AccountError;
use Gandalf\Component\Security\Error\AccountException;
use Gandalf\Component\Security\Factory\AccountFactory;
use Gandalf\Component\Security\Factory\TokenFactory;
use Gandalf\Component\Security\Hasher\TokenHasher;
use Gandalf\Component\Security\Persistence\AccountStore;
use Gandalf\Component\Security\Persistence\TokenStore;

class Handler implements ActionHandler
{
    public function __construct(
        private readonly AccountFactory $accountFactory,
        private readonly AccountStore $accountStore,
        private readonly TokenFactory $tokenFactory,
        private readonly TokenStore $tokenStore,
        private readonly TokenHasher $tokenHasher,
    ) {
    }

    public function __invoke(Command $command): Response
    {
        $token = $command->token;

        if (!$this->tokenHasher->verify($command->rawToken, $token->tokenHash)) {
            throw new AccountException(AccountError::InvalidToken);
        }

        if ($token->isExpired()) {
            throw new AccountException(AccountError::TokenExpired);
        }

        $account = $token->account;

        // Revoke the token
        $revokedToken = $this->tokenFactory->create()
            ->with(account: $account)
            ->with(intention: $token->intention)
            ->with(tokenHash: $token->tokenHash)
            ->with(expiresAt: new \DateTimeImmutable())
            ->with(label: $token->label)
            ->with(scopes: $token->scopes)
            ->with(createdAt: $token->createdAt)
            ->with(uuid: $token->uuid)
            ->build();

        $this->tokenStore->sync($revokedToken);

        return new Response($account);
    }
}
