<?php

declare(strict_types=1);

namespace Gandalf\Bridge\Symfony\Bundle\Controller;

use Cortex\Bridge\Symfony\Controller\ControllerInterface;
use Gandalf\Bridge\Symfony\Bundle\Form\CredentialEditType;
use Gandalf\Component\Security\Action\CredentialEdit;
use Gandalf\Component\Security\Model\Credential;
use Gandalf\Component\Security\Service\CredentialVault;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CredentialEditAction implements ControllerInterface
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly CredentialEdit\Handler $handler,
        private readonly CredentialVault $vault,
    ) {
    }

    /**
     * @return array<string, mixed>|Response
     */
    public function __invoke(Credential $credential, Request $request): array|Response
    {
        $decryptedData = [];
        try {
            $decryptedData = '' !== $credential->data ? $this->vault->decrypt($credential->data) : [];
        } catch (\RuntimeException) {
            // Cannot decrypt — show empty form
        }

        $form = $this->formFactory->create(
            CredentialEditType::class,
            [
                'name' => $credential->name,
                'provider' => $credential->provider->value,
                'data' => json_encode($decryptedData, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES),
            ],
            ['method' => 'POST'],
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $rawData = json_decode($formData['data'] ?? '{}', true, 512, \JSON_THROW_ON_ERROR);

            $this->handler->__invoke(new CredentialEdit\Command(
                uuid: $credential->uuid,
                name: $formData['name'],
                rawData: $rawData,
            ));

            $session = $request->getSession();
            if ($session instanceof Session) {
                $session->getFlashBag()->add('success', [
                    'title' => 'credential.edit.success.title',
                    'message' => 'credential.edit.success.message',
                    'params' => ['name' => $credential->name],
                    'domain' => 'gandalf',
                ]);
            }

            return new RedirectResponse(
                $this->urlGenerator->generate('gandalf_credential_edit', ['uuid' => $credential->uuid])
            );
        }

        return [
            'credential' => $credential,
            'form' => $form->createView(),
        ];
    }
}
