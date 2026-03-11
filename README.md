# Gandalf

[![CI](https://github.com/Nyx-Corp/Gandalf/actions/workflows/ci.yml/badge.svg)](https://github.com/Nyx-Corp/Gandalf/actions/workflows/ci.yml)

Composant de securite pour applications Symfony. Depend de [Cortex](https://github.com/Nyx-Corp/Cortex).

**Stack** : PHP 8.5, Symfony 7.4

## Architecture

```
Gandalf/
├── src/
│   ├── Component/Security/
│   │   ├── Action/          # AskPasswordReset, CreatePassword, ValidateEmail, TokenCreate/Revoke, AccountCreate/Update/Archive
│   │   ├── Error/           # AccountError enum, AccountException
│   │   ├── Factory/         # AccountFactory, TokenFactory (extend Cortex ModelFactory)
│   │   ├── Model/           # Account, Token (+Collections)
│   │   ├── Persistence/     # AccountStore, TokenStore
│   │   └── Hasher/          # TokenHasher (SHA-256), PasswordHasherInterface
│   │
│   └── Bridge/Symfony/Security/
│       ├── ApiTokenAuthenticator      # Bearer auth (Symfony custom authenticator)
│       ├── ApiTokenProviderInterface  # Contract: token → UserInterface
│       ├── SecuredActionToolProvider  # MCP decorator (filters tools by ACL)
│       ├── SymfonyPasswordHasher     # Bridge to Symfony's PasswordHasherInterface
│       ├── RateLimitSubscriber       # HTTP rate limiting (kernel.request)
│       └── RateLimitedActionToolProvider  # MCP rate limiting decorator
│
└── tests/
    └── Unit/                # Unit tests
```

## Composants

### Component/Security

| Module | Description |
|--------|-------------|
| **Action** | Actions metier : creation de compte, reset de mot de passe, validation email, gestion de tokens |
| **Error** | Enum `AccountError` et `AccountException` pour les erreurs du domaine securite |
| **Factory** | `AccountFactory`, `TokenFactory` — etendent `ModelFactory` de Cortex |
| **Model** | `Account`, `Token` et leurs collections |
| **Persistence** | `AccountStore`, `TokenStore` — persistance via Cortex `ModelStore` |
| **Hasher** | `TokenHasher` (SHA-256), `PasswordHasherInterface` — contrats de hachage |

### Bridge/Symfony/Security

| Classe | Description |
|--------|-------------|
| **ApiTokenAuthenticator** | Authentification Bearer (custom authenticator Symfony) |
| **ApiTokenProviderInterface** | Contrat : token → `UserInterface` |
| **SecuredActionToolProvider** | Decorateur MCP qui filtre les tools par ACL |
| **SymfonyPasswordHasher** | Bridge vers le `PasswordHasherInterface` de Symfony |
| **RateLimitSubscriber** | Rate limiting HTTP sur `kernel.request` |
| **RateLimitedActionToolProvider** | Decorateur MCP pour le rate limiting des tools |

## Dependances

Gandalf depend de Cortex. Imports utilises :

- `ModelFactory`, `ModelStore` — cycle de vie des modeles
- `Archivable`, `Uuidentifiable` — traits de modeles
- `ActionHandler` — pattern Command/Handler/Event
- `DomainException` — exceptions metier
- `Email`, `HashedPassword` — value objects

**Cortex n'a AUCUNE dependance vers Gandalf.**

## Integration

Gandalf est embarque via `git subtree` dans les projets NyxCorp (`src/Lib/Gandalf/`).

Un hook `pre-push` synchronise automatiquement les modifications vers ce repo a chaque `git push origin`. Aucune commande manuelle necessaire.

### PHP

```php
use Gandalf\Component\Security\Factory\AccountFactory;
use Gandalf\Component\Security\Model\Account;
use Gandalf\Bridge\Symfony\Security\ApiTokenAuthenticator;
```

## Developpement

### Installation standalone

```bash
composer install
```

### QA

```bash
vendor/bin/phpunit                          # Tests unitaires
vendor/bin/phpstan analyse --no-progress    # Analyse statique
```

## Licence

Proprietary — NyxCorp
