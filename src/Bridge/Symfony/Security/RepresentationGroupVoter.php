<?php

declare(strict_types=1);

namespace Gandalf\Bridge\Symfony\Security;

use Cortex\Bridge\Symfony\Serializer\RepresentationRegistry;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * Votes on representation group access using scopes in role_hierarchy.
 *
 * Scope format: model@group (e.g., page@detail, article@full).
 * Supports fnmatch() wildcards (e.g., *@detail, page@*).
 */
class RepresentationGroupVoter extends Voter
{
    public function __construct(
        private readonly RoleHierarchyInterface $roleHierarchy,
        private readonly RepresentationRegistry $registry,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === 'REPRESENTATION_VIEW'
            && is_array($subject)
            && count($subject) === 2;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        [$modelClass, $group] = $subject;

        if ($this->registry->has($modelClass)
            && in_array($group, $this->registry->get($modelClass)->publicGroups(), true)
        ) {
            return true;
        }

        $scope = strtolower((new \ReflectionClass($modelClass))->getShortName()) . '@' . $group;
        $reachableRoles = $this->roleHierarchy->getReachableRoleNames($token->getRoleNames());

        foreach ($reachableRoles as $role) {
            if ($role === $scope || fnmatch($role, $scope)) {
                return true;
            }
        }

        return false;
    }
}
