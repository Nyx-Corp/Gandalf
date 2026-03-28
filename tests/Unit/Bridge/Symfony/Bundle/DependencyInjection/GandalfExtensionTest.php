<?php

declare(strict_types=1);

namespace Gandalf\Tests\Unit\Bridge\Symfony\Bundle\DependencyInjection;

use Gandalf\Bridge\Symfony\Bundle\Controller\AccountEditAction;
use Gandalf\Bridge\Symfony\Bundle\Controller\AccountListAction;
use Gandalf\Bridge\Symfony\Bundle\Controller\LoginController;
use Gandalf\Bridge\Symfony\Bundle\Controller\TokenListAction;
use Gandalf\Bridge\Symfony\Bundle\DependencyInjection\GandalfExtension;
use Gandalf\Bridge\Symfony\Bundle\Form\AccountAclType;
use Gandalf\Bridge\Symfony\Bundle\Form\LoginType;
use Gandalf\Bridge\Symfony\Security\CorsSubscriber;
use Gandalf\Bridge\Symfony\Security\SymfonyPasswordHasher;
use Gandalf\Component\Security\Factory\AccountFactory;
use Gandalf\Component\Security\Factory\TokenFactory;
use Gandalf\Component\Security\Hasher\PasswordHasherInterface;
use Gandalf\Component\Security\Hasher\TokenHasher;
use Gandalf\Component\Security\Persistence\AccountStore;
use Gandalf\Component\Security\Persistence\TokenStore;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[CoversClass(GandalfExtension::class)]
class GandalfExtensionTest extends TestCase
{
    private ContainerBuilder $container;
    private GandalfExtension $extension;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.environment', 'test');
        $this->extension = new GandalfExtension();
    }

    // =======================================================================
    // SERVICE REGISTRATION
    // =======================================================================

    public function testLoadsSecurityServices(): void
    {
        $this->extension->load([[]], $this->container);

        $this->assertTrue($this->container->has(TokenHasher::class));
        $this->assertTrue($this->container->has(SymfonyPasswordHasher::class));
        $this->assertTrue($this->container->has(AccountFactory::class));
        $this->assertTrue($this->container->has(TokenFactory::class));
        $this->assertTrue($this->container->has(AccountStore::class));
        $this->assertTrue($this->container->has(TokenStore::class));
    }

    public function testLoadsPasswordHasherAlias(): void
    {
        $this->extension->load([[]], $this->container);

        $this->assertTrue($this->container->has(PasswordHasherInterface::class));
        $alias = $this->container->getAlias(PasswordHasherInterface::class);
        $this->assertSame(SymfonyPasswordHasher::class, (string) $alias);
    }

    public function testLoadsBundleControllers(): void
    {
        $this->extension->load([[]], $this->container);

        $this->assertTrue($this->container->has(LoginController::class));
        $this->assertTrue($this->container->has(AccountListAction::class));
        $this->assertTrue($this->container->has(AccountEditAction::class));
        $this->assertTrue($this->container->has(TokenListAction::class));
    }

    public function testLoadsBundleFormTypes(): void
    {
        $this->extension->load([[]], $this->container);

        $this->assertTrue($this->container->has(LoginType::class));
        $this->assertTrue($this->container->has(AccountAclType::class));
    }

    // =======================================================================
    // CORS
    // =======================================================================

    public function testCorsEnabledRegistersSubscriberWithApiHost(): void
    {
        $this->extension->load([['cors' => ['enabled' => true, 'api_host' => 'api.test']]], $this->container);

        $this->assertTrue($this->container->has(CorsSubscriber::class));
        $definition = $this->container->getDefinition(CorsSubscriber::class);
        $this->assertSame('api.test', $definition->getArgument('$apiHost'));
    }

    public function testCorsDisabledDoesNotSetApiHostArgument(): void
    {
        $this->extension->load([[]], $this->container);

        // CorsSubscriber may exist from resource scan, but should NOT have $apiHost configured
        if ($this->container->has(CorsSubscriber::class)) {
            $definition = $this->container->getDefinition(CorsSubscriber::class);
            $this->assertEmpty($definition->getArguments(), 'CorsSubscriber should not have explicit arguments when cors is disabled');
        } else {
            $this->assertFalse($this->container->has(CorsSubscriber::class));
        }
    }

    // =======================================================================
    // ADMIN
    // =======================================================================

    public function testAdminEnabledSetsParameters(): void
    {
        $this->extension->load([['admin' => [
            'layout' => 'admin/base.html.twig',
            'roles' => ['ROLE_USER' => 'User', 'ROLE_ADMIN' => 'Admin'],
        ]]], $this->container);

        $this->assertSame(['ROLE_USER' => 'User', 'ROLE_ADMIN' => 'Admin'], $this->container->getParameter('gandalf.admin.roles'));
    }

    public function testAdminDisabledDoesNotSetParameters(): void
    {
        $this->extension->load([['admin' => ['enabled' => false]]], $this->container);

        $this->assertFalse($this->container->hasParameter('gandalf.admin.roles'));
    }
}
