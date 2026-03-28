# Templates — `@_gandalf`

Gandalf provides structural templates under the `@_gandalf` Twig namespace. The host project extends them and provides styling — Gandalf imposes no CSS.

## Pattern

Same as Cortex's `@_theme`:

1. Gandalf templates define **semantic blocks** with **BEM CSS classes** (`gandalf-*`)
2. Your project creates templates that `{% extends '@_gandalf/...' %}`
3. Fill the blocks (logo, splash, meta, footer)
4. Style `gandalf-*` classes in your CSS (Tailwind `@apply`, custom properties, plain CSS)

## Available templates

### `@_gandalf/security/layout.html.twig`

Authentication page layout (login, password reset). Two-column: form + splash.

| Block | Purpose |
|-------|---------|
| `meta` | `<head>` additions (vite entries, favicon) |
| `logo` | Brand logo image/text |
| `page_title` | Heading (h2) |
| `page_headline` | Sub-heading |
| `main` | Form content |
| `splash` | Right-side illustration |
| `footer` | Footer (scripts, links) |

CSS classes to style:
- `.gandalf-auth-layout` — outer flex container
- `.gandalf-auth-form` — left column (form side)
- `.gandalf-auth-form__inner` — inner wrapper with max-width
- `.gandalf-auth-form__header` — logo + title area
- `.gandalf-auth-form__title` — h2 element
- `.gandalf-auth-form__headline` — subtitle paragraph
- `.gandalf-auth-form__content` — form area
- `.gandalf-auth-splash` — right column (illustration)

### `@_gandalf/security/login.html.twig`

Extends `layout.html.twig`. Renders the `LoginType` form with error handling.

Additional CSS classes:
- `.gandalf-login-form` — the `<form>` element
- `.gandalf-alert` — alert container
- `.gandalf-alert--error` — error variant

### `@_gandalf/security/form_theme.html.twig`

Form theme for authentication fields. Use via `{% use '@_gandalf/security/form_theme.html.twig' %}`.

CSS classes:
- `.gandalf-field` — field wrapper
- `.gandalf-field__label` — label element
- `.gandalf-field__input` — input container
- `.gandalf-field__widget` — the input element itself
- `.gandalf-field--submit` — submit button wrapper
- `.gandalf-field__submit` — submit button

### `@_gandalf/admin/account/index.html.twig`

Account list. Uses `@_theme/layout/list.html.twig` from Cortex. Extends the `gandalf_admin_layout` Twig global (configurable via `gandalf.admin.layout`).

### `@_gandalf/admin/account/edit.html.twig`

ACL edit form with role checkboxes.

### `@_gandalf/admin/token/index.html.twig`

Token list for a single account with revoke action.

### `@_gandalf/admin/layout.html.twig`

Minimal fallback layout. Used only if the project doesn't configure `gandalf.admin.layout`.

## Integration example

### Login page

```twig
{# templates/admin/security/login.html.twig #}
{% extends '@_gandalf/security/login.html.twig' %}

{% block meta %}
    {{ vite_entry_link_tags('admin_css') }}
{% endblock %}

{% block title %}Login - My App{% endblock %}

{% block logo %}
    <img src="/images/logo.svg" alt="My App">
{% endblock %}

{% block splash %}
    <img class="absolute inset-0 size-full object-cover"
         src="/images/login-splash.jpg" alt="">
{% endblock %}

{% block footer %}
    {{ vite_entry_script_tags('admin_js') }}
{% endblock %}
```

### CSS

```css
/* Style Gandalf classes with your project's design system */
.gandalf-auth-layout {
    display: flex;
    min-height: 100vh;
}

.gandalf-auth-form {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 3rem 1rem;
}

@media (min-width: 1024px) {
    .gandalf-auth-form { padding: 3rem 5rem; }
}

.gandalf-auth-form__inner {
    width: 100%;
    max-width: 24rem;
    margin: 0 auto;
}

.gandalf-auth-form__title {
    margin-top: 2rem;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-foreground);
}

.gandalf-auth-form__headline {
    margin-top: 0.5rem;
    font-size: 0.875rem;
    color: var(--color-foreground);
}

.gandalf-auth-form__content {
    margin-top: 2.5rem;
}

.gandalf-auth-splash {
    position: relative;
    display: none;
    flex: 1;
}

@media (min-width: 1024px) {
    .gandalf-auth-splash { display: block; }
}

.gandalf-login-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.gandalf-field__label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-foreground);
}

.gandalf-field__input {
    margin-top: 0.5rem;
}

.gandalf-field__widget {
    display: block;
    width: 100%;
    padding: 0.375rem 0.75rem;
    background: var(--color-card);
    color: var(--color-foreground);
    border: 1px solid var(--color-input);
    outline: none;
}

.gandalf-field__widget:focus {
    border-color: var(--color-primary);
    box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-primary) 20%, transparent);
}

.gandalf-field--submit {
    margin-top: 2rem;
}

.gandalf-field__submit {
    display: flex;
    width: 100%;
    justify-content: center;
    padding: 0.375rem 0.75rem;
    font-weight: 600;
    font-size: 0.875rem;
    color: white;
    background: var(--color-primary);
    cursor: pointer;
}

.gandalf-field__submit:hover {
    opacity: 0.9;
}

.gandalf-alert--error {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    color: var(--color-destructive);
    border: 1px solid color-mix(in srgb, var(--color-destructive) 30%, transparent);
    background: color-mix(in srgb, var(--color-destructive) 10%, transparent);
}
```

## Translations

Gandalf ships translations in `fr` and `en` under the `gandalf` domain. Override them in your project:

```yaml
# translations/gandalf+intl-icu.fr.yaml
login.title: Connexion à Mon App
login.headline: Bienvenue sur votre espace
```
