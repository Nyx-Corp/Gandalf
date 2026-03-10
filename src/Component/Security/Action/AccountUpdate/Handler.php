<?php

namespace Gandalf\Component\Security\Action\AccountUpdate;

use Cortex\Component\Action\ActionHandler;
use Gandalf\Component\Security\Factory\AccountFactory;
use Gandalf\Component\Security\Hasher\PasswordHasherInterface;
use Gandalf\Component\Security\Persistence\AccountStore;

class Handler implements ActionHandler
{
    public function __construct(
        private readonly AccountFactory $factory,
        private readonly AccountStore $store,
        private readonly PasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(Command $command): Response
    {
        $existing = $this->factory->query()->filter(uuid: $command->uuid)->first();

        $builder = $this->factory->create()
            ->with(uuid: $command->uuid)
            ->with(username: $command->username);

        if (null !== $command->acl) {
            $acl = $command->acl;
            if (!\in_array('ROLE_USER', $acl, true)) {
                $acl[] = 'ROLE_USER';
            }
            $builder->with(acl: $acl);
        } elseif ($existing) {
            $builder->with(acl: $existing->acl);
        }

        if ($command->plainPassword) {
            $builder->with(password: $this->passwordHasher->hashPassword($command->plainPassword));
        } elseif ($existing) {
            $builder->with(password: $existing->password);
        }

        $account = $builder->build();
        $this->store->sync($account);

        return new Response($account);
    }
}
