# Gandalf — Security for Cortex

Authentication, token management, rate limiting, CORS, and MCP access control for Cortex-based Symfony projects.

## Features

- **GandalfBundle** — auto-registers all services, configurable via `gandalf.yaml`
- **Account & Token** — DDD models with factories, stores, and action handlers
- **Login UI** — extensible templates under `@_gandalf` namespace
- **Admin module** — account list, ACL editing, token management
- **API auth** — stateless Bearer token authenticator
- **Rate limiting** — per-route, per-API, per-MCP limiter maps
- **CORS** — configurable subscriber for API hosts
- **MCP access control** — `SecuredActionToolProvider` enforces Symfony access_control on MCP tools

## Installation

### 1. Add the submodule

```bash
git submodule add <repo-url> src/Lib/Gandalf
```

### 2. Register the bundle

```php
// config/bundles.php
return [
    // ...
    Gandalf\Bridge\Symfony\Bundle\GandalfBundle::class => ['all' => true],
];
```

### 3. Register the Twig namespace

```yaml
# config/packages/twig.yaml
twig:
    paths:
        '%kernel.project_dir%/src/Lib/Gandalf/src/Bridge/Symfony/Bundle/Resources/views': _gandalf
```

### 4. Create the bundle configuration

```yaml
# config/packages/gandalf.yaml
gandalf:
    cors:
        enabled: true
        api_host: '%env(API_HOST)%'
    admin:
        enabled: true
        layout: 'admin/base.html.twig'    # your project's admin layout
        roles:
            ROLE_USER: User
            ROLE_ADMIN: Administrator
```

### 5. Project-specific services

In your `config/services.yaml`, add only what the bundle can't know:

```yaml
# Alias to YOUR implementation of ApiTokenProviderInterface
Gandalf\Bridge\Symfony\Security\ApiTokenProviderInterface:
    alias: Infrastructure\Symfony\Security\ApiTokenProvider

# Rate limiter arguments (your limiter services)
Gandalf\Bridge\Symfony\Security\RateLimitSubscriber:
    arguments:
        $routeLimiters:
            login_check: '@limiter.login_attempt'
        $apiLimiters:
            authenticated: '@limiter.api_per_account'
            anonymous: '@limiter.api_anonymous'
        $apiPathPrefix: '/p'
```

### 6. Import routes

```yaml
# config/routes/admin.yaml (or modules/security.yaml)
_gandalf_security:
    resource: '@GandalfBundle/Resources/config/routes_security.yaml'

gandalf_admin:
    resource: '@GandalfBundle/Resources/config/routes_admin.yaml'
```

### 7. Doctrine mappers

Create DBAL mappers for Account and Token in your `Infrastructure/` layer:

```
src/Infrastructure/Doctrine/Mapper/AccountMapper.php
src/Infrastructure/Doctrine/Mapper/TokenMapper.php
```

These map Gandalf's domain models to your database tables. See the Cortex architecture docs for the mapper pattern.

### 8. Security configuration

Configure firewalls and access_control in `config/packages/security.yaml`:

```yaml
security:
    firewalls:
        api:
            stateless: true
            custom_authenticators:
                - Gandalf\Bridge\Symfony\Security\ApiTokenAuthenticator
        main:
            form_login:
                login_path: login_form     # Gandalf route name
                check_path: login_check    # Gandalf route name
            logout:
                path: logout               # Gandalf route name

    access_control:
        - { path: ^/login, roles: PUBLIC_ACCESS }
        - { path: ^/security, roles: ROLE_SUPER_ADMIN }
        - { path: ^/, roles: ROLE_ADMIN }
```

## What the bundle auto-registers

All of the following are registered automatically — no `services.yaml` needed:

| Service | Type |
|---------|------|
| `TokenHasher` | SHA-256 token generation & verification |
| `SymfonyPasswordHasher` | Bridge to Symfony password hashing |
| `PasswordHasherInterface` | Alias → `SymfonyPasswordHasher` |
| `ApiTokenAuthenticator` | Stateless Bearer token auth |
| `AccountFactory`, `TokenFactory` | Cortex model factories |
| `AccountStore`, `TokenStore` | Cortex model stores |
| `RateLimitSubscriber` | HTTP rate limiting (defaults to no-op) |
| 8 Action Handlers | AccountCreate, AccountUpdate, AccountArchive, TokenCreate, TokenRevoke, AskPasswordReset, CreatePassword, ValidateEmail |
| `LoginController` | Admin login page |
| `AccountListAction`, `AccountEditAction`, `TokenListAction` | Admin module controllers |
| `LoginType`, `AccountAclType` | Form types |

`CorsSubscriber` is registered only when `gandalf.cors.enabled: true`.

## Documentation

- [Templates](templates.md) — the `@_gandalf` template system
- [Security](security.md) — API auth, rate limiting, CORS, MCP access control
- [Admin module](admin.md) — account & token management UI
