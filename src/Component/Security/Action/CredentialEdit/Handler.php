<?php

declare(strict_types=1);

namespace Gandalf\Component\Security\Action\CredentialEdit;

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
        $existing = $this->factory->query()->filter(uuid: $command->uuid)->first();

        $credential = $this->factory->create()
            ->with(
                name: $command->name ?? $existing->name,
                provider: $command->provider ?? $existing->provider,
                data: null !== $command->rawData
                    ? $this->vault->encrypt($command->rawData)
                    : $existing->data,
                expiresAt: $command->expiresAt ?? $existing->expiresAt,
                refreshToken: null !== $command->rawRefreshToken
                    ? $this->vault->encrypt(['token' => $command->rawRefreshToken])
                    : $existing->refreshToken,
                uuid: $existing->uuid,
            )
            ->build();

        $this->store->sync($credential);

        return new Response($credential);
    }
}
