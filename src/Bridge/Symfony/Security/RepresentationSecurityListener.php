<?php

declare(strict_types=1);

namespace Gandalf\Bridge\Symfony\Security;

use Cortex\Bridge\Symfony\Serializer\Event\PreDenormalizeEvent;
use Cortex\Bridge\Symfony\Serializer\Event\PreNormalizeEvent;
use Cortex\Bridge\Symfony\Serializer\RepresentationRegistry;
use Cortex\Component\Mapper\ModelRepresentation;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Enforces representation group access via Symfony security voters.
 *
 * Listens to PreNormalize/PreDenormalize events and downgrades
 * the group if the current user lacks the required scope.
 */
#[AsEventListener(event: PreNormalizeEvent::class, priority: 100)]
#[AsEventListener(event: PreDenormalizeEvent::class, priority: 100)]
class RepresentationSecurityListener
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $authChecker,
        private readonly RepresentationRegistry $registry,
    ) {}

    public function __invoke(PreNormalizeEvent|PreDenormalizeEvent $event): void
    {
        if (!$this->registry->has($event->modelClass)) {
            return;
        }

        $representation = $this->registry->get($event->modelClass);
        $group = $event->getGroup();

        if (in_array($group, $representation->publicGroups(), true)) {
            return;
        }

        if ($this->authChecker->isGranted('REPRESENTATION_VIEW', [$event->modelClass, $group])) {
            return;
        }

        foreach ($this->resolveFallbacks($representation, $group) as $fallback) {
            if (in_array($fallback, $representation->publicGroups(), true)
                || $this->authChecker->isGranted('REPRESENTATION_VIEW', [$event->modelClass, $fallback])
            ) {
                $event->setGroup($fallback);
                $event->stopPropagation();

                return;
            }
        }

        $event->setGroup('id');
        $event->stopPropagation();
    }

    /**
     * @return list<string>
     */
    private function resolveFallbacks(ModelRepresentation $representation, string $currentGroup): array
    {
        $allGroups = array_keys($representation->groups());

        return array_values(array_diff($allGroups, [$currentGroup, 'store']));
    }
}
