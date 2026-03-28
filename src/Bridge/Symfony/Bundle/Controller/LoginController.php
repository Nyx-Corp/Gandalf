<?php

declare(strict_types=1);

namespace Gandalf\Bridge\Symfony\Bundle\Controller;

use Cortex\Bridge\Symfony\Controller\ControllerInterface;
use Gandalf\Bridge\Symfony\Bundle\Form\LoginType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController implements ControllerInterface
{
    /**
     * @return array<string, mixed>
     */
    public function login(AuthenticationUtils $authenticationUtils, FormFactoryInterface $formFactory): array
    {
        $form = $formFactory->createNamed(
            'login',
            LoginType::class,
            [
                'username' => $authenticationUtils->getLastUsername(),
            ],
            [
                'action' => $this->generateUrl('login_check'),
                'method' => 'POST',
            ]
        );

        return [
            'form' => $form->createView(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ];
    }

    public function logout(): never
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
