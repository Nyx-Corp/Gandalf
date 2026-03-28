<?php

declare(strict_types=1);

namespace Gandalf\Bridge\Symfony\Bundle\Controller;

use Cortex\Bridge\Symfony\Controller\ControllerInterface;
use Gandalf\Component\Security\Action\TokenRevoke;
use Gandalf\Component\Security\Factory\AccountFactory;
use Gandalf\Component\Security\Factory\TokenFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

class TokenListAction implements ControllerInterface
{
    public function __construct(
        private readonly AccountFactory $accountFactory,
        private readonly TokenFactory $tokenFactory,
        private readonly TokenRevoke\Handler $revokeHandler,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @return array<string, mixed>|Response
     */
    public function __invoke(string $uuid, Request $request): array|Response
    {
        $accountUuid = Uuid::fromString($uuid);
        $account = $this->accountFactory->query()->filter(uuid: $accountUuid)->first();

        if (!$account) {
            throw new NotFoundHttpException('Account not found.');
        }

        // Handle token revocation (POST)
        if ($request->isMethod('POST') && $request->request->has('revoke_token')) {
            $tokenUuid = Uuid::fromString($request->request->getString('revoke_token'));
            $token = $this->tokenFactory->query()->filter(uuid: $tokenUuid)->first();

            if ($token) {
                $this->revokeHandler->__invoke(new TokenRevoke\Command(token: $token));

                $session = $request->getSession();
                if ($session instanceof Session) {
                    $session->getFlashBag()->add('success', [
                        'title' => 'token.revoke.success.title',
                        'message' => 'token.revoke.success.message',
                        'domain' => 'gandalf',
                    ]);
                }
            }

            return new RedirectResponse(
                $this->urlGenerator->generate('gandalf_token_list', ['uuid' => $uuid])
            );
        }

        $tokens = $this->tokenFactory->query()
            ->filter(account: $account)
            ->all();

        return [
            'account' => $account,
            'tokens' => $tokens,
        ];
    }
}
