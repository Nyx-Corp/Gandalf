<?php

declare(strict_types=1);

namespace Gandalf\Tests\Unit\Bridge\Symfony\Bundle\DependencyInjection;

use Gandalf\Bridge\Symfony\Bundle\DependencyInjection\Configuration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

#[CoversClass(Configuration::class)]
class ConfigurationTest extends TestCase
{
    private Processor $processor;
    private Configuration $configuration;

    protected function setUp(): void
    {
        $this->processor = new Processor();
        $this->configuration = new Configuration();
    }

    // =======================================================================
    // DEFAULTS
    // =======================================================================

    public function testEmptyConfigReturnsDefaults(): void
    {
        $config = $this->process([]);

        $this->assertFalse($config['cors']['enabled']);
        $this->assertTrue($config['admin']['enabled']);
        $this->assertSame('@_gandalf/admin/layout.html.twig', $config['admin']['layout']);
        $this->assertSame(['ROLE_USER' => 'Utilisateur', 'ROLE_ADMIN' => 'Administrateur'], $config['admin']['roles']);
    }

    // =======================================================================
    // CORS
    // =======================================================================

    public function testCorsDisabledByDefault(): void
    {
        $config = $this->process([]);

        $this->assertFalse($config['cors']['enabled']);
    }

    public function testCorsEnabledRequiresApiHost(): void
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);

        $this->process([
            'cors' => ['enabled' => true],
        ]);
    }

    public function testCorsEnabledWithApiHost(): void
    {
        $config = $this->process([
            'cors' => ['enabled' => true, 'api_host' => 'api.example.com'],
        ]);

        $this->assertTrue($config['cors']['enabled']);
        $this->assertSame('api.example.com', $config['cors']['api_host']);
    }

    // =======================================================================
    // ADMIN
    // =======================================================================

    public function testAdminEnabledByDefault(): void
    {
        $config = $this->process([]);

        $this->assertTrue($config['admin']['enabled']);
    }

    public function testAdminCanBeDisabled(): void
    {
        $config = $this->process([
            'admin' => ['enabled' => false],
        ]);

        $this->assertFalse($config['admin']['enabled']);
    }

    public function testAdminCustomLayout(): void
    {
        $config = $this->process([
            'admin' => ['layout' => 'admin/base.html.twig'],
        ]);

        $this->assertSame('admin/base.html.twig', $config['admin']['layout']);
    }

    public function testAdminCustomRoles(): void
    {
        $config = $this->process([
            'admin' => [
                'roles' => [
                    'ROLE_USER' => 'User',
                    'ROLE_ADMIN' => 'Admin',
                    'ROLE_SUPER_ADMIN' => 'Super Admin',
                ],
            ],
        ]);

        $this->assertCount(3, $config['admin']['roles']);
        $this->assertSame('Super Admin', $config['admin']['roles']['ROLE_SUPER_ADMIN']);
    }

    // =======================================================================
    // HELPERS
    // =======================================================================

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function process(array $config): array
    {
        return $this->processor->processConfiguration($this->configuration, ['gandalf' => $config]);
    }
}
