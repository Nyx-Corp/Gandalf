<?php

declare(strict_types=1);

namespace Gandalf\Component\Security\Action\CredentialCreate;

use Cortex\Component\Action\ActionHandler;
use Gandalf\Component\Security\Factory\CredentialFactory;
use Gandalf\Component\Security\Persistence\CredentialStore;
use Gandalf\Component\Security\Service\CredentialVault;

class Handler implements ActionHandler
{
    public function __construct(
        private readonly CredentialFactory $factory,
        private readonly CredentialStore $store,
        private readonly CredentialVault $vault,
    ) {
    }

    public function __invoke(Command $command): Response
    {
        $credential = $this->factory->create()
            ->with(
                name: $command->name,
                provider: $command->provider,
                data: $this->vault->encrypt($command->rawData),
                expiresAt: $command->expiresAt,
                refreshToken: $command->rawRefreshToken
                    ? $this->vault->encrypt(['token' => $command->rawRefreshToken])
                    : null,
            )
            ->build();

        $this->store->sync($credential);

        return new Response($credential);
    }
}
