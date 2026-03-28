<?php

declare(strict_types=1);

namespace Gandalf\Bridge\Symfony\Bundle\Controller;

use Cortex\Bridge\Symfony\Controller\ControllerInterface;
use Gandalf\Bridge\Symfony\Bundle\Form\AccountAclType;
use Gandalf\Component\Security\Action\AccountUpdate;
use Gandalf\Component\Security\Model\Account;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AccountEditAction implements ControllerInterface
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly AccountUpdate\Handler $handler,
    ) {
    }

    /**
     * @return array<string, mixed>|Response
     */
    public function __invoke(Account $account, Request $request): array|Response
    {
        $form = $this->formFactory->create(
            AccountAclType::class,
            ['acl' => $account->acl],
            ['method' => 'POST'],
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $this->handler->__invoke(new AccountUpdate\Command(
                uuid: $account->uuid,
                username: $account->username,
                acl: $data['acl'],
            ));

            $session = $request->getSession();
            if ($session instanceof Session) {
                $session->getFlashBag()->add('success', [
                    'title' => 'account.edit.success.title',
                    'message' => 'account.edit.success.message',
                    'params' => ['username' => (string) $account->username],
                    'domain' => 'gandalf',
                ]);
            }

            return new RedirectResponse(
                $this->urlGenerator->generate('gandalf_account_edit', ['uuid' => $account->uuid])
            );
        }

        return [
            'account' => $account,
            'form' => $form->createView(),
        ];
    }
}
