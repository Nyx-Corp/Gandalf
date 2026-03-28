# Admin Module — Account & Token Management

Gandalf provides a reusable admin module for managing security accounts (ACL editing) and API tokens (viewing, revoking).

## Activation

```yaml
# config/packages/gandalf.yaml
gandalf:
    admin:
        enabled: true
        layout: 'admin/base.html.twig'
        roles:
            ROLE_USER: User
            ROLE_ADMIN: Administrator
            ROLE_SUPER_ADMIN: Super admin
```

## Configuration

| Key | Default | Description |
|-----|---------|-------------|
| `enabled` | `true` | Enable the admin module |
| `layout` | `@_gandalf/admin/layout.html.twig` | Parent template for admin pages |
| `roles` | `ROLE_USER`, `ROLE_ADMIN` | Role choices in the ACL edit form |

### Layout

The `layout` config defines the Twig template that admin pages extend. It's injected as a Twig global `gandalf_admin_layout`.

Set this to your project's admin base template (e.g., `admin/base.html.twig`) so Gandalf pages inherit your sidebar, header, and styling. Gandalf admin templates use `@_theme` Cortex components (list, forms, breadcrumbs), so they integrate visually with any Cortex-based admin.

If not configured, pages use `@_gandalf/admin/layout.html.twig` — a bare HTML fallback.

### Roles

The `roles` config is a `ROLE_* => label` mapping. These are displayed as checkboxes in the account edit form. Define the roles that make sense for your project.

## Routes

Import in your admin routes:

```yaml
# config/routes/admin.yaml
gandalf_admin:
    resource: '@GandalfBundle/Resources/config/routes_admin.yaml'
```

| Route | Path | Method | Description |
|-------|------|--------|-------------|
| `gandalf_account_list` | `/security/accounts` | GET | Account list with search & archive toggle |
| `gandalf_account_edit` | `/security/accounts/{uuid}/edit` | GET, POST | Edit account ACL roles |
| `gandalf_token_list` | `/security/accounts/{uuid}/tokens` | GET, POST | View tokens, revoke via POST |

## Security

Protect the admin routes in `security.yaml`:

```yaml
access_control:
    - { path: ^/security, host: "%env(ADMIN_HOST)%", roles: ROLE_SUPER_ADMIN }
```

## Sidebar

Add a menu entry in your admin sidebar:

```twig
{ label: 'Accounts', icon: 'shield', route: 'gandalf_account_list' }
```

## Pages

### Account list

Lists all Gandalf accounts with:
- Username (email) column, sortable
- Edit and token links per row
- Archive toggle (accounts use the `Archivable` trait)
- Search via Cortex query decorator

### Account ACL edit

Checkbox form with available roles from `gandalf.admin.roles`. Dispatches `AccountUpdate\Command` on submit. Always ensures `ROLE_USER` is present.

### Token list

Lists all tokens for a given account:
- Label, intention, expiration, scopes columns
- Expired tokens shown with reduced opacity
- Revoke button dispatches `TokenRevoke\Command` (sets `expiresAt` to now)
- Confirmation dialog before revoke
