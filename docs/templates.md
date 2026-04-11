# Templates — `@_gandalf`

Gandalf provides structural templates under the `@_gandalf` Twig namespace. The host project extends them and provides styling — Gandalf imposes no CSS.

## Pattern

Same as Cortex's `@_theme`:

1. Gandalf templates define **semantic blocks** with **BEM CSS classes** (`gandalf-*`)
2. Your project creates templates that extend `@_gandalf/...` and fill the blocks (logo, splash, meta, footer)
3. **You must supply the CSS for every `gandalf-*` class** — they have no default styling
4. Style them with Tailwind `@apply`, CSS custom properties, plain CSS — your call

## ⚠️ Important: Gandalf `security/layout.html.twig` is a fragment

`@_gandalf/security/layout.html.twig` produces **a bare HTML fragment** — it contains the `.gandalf-auth-layout` div but no `<!doctype>`, `<html>`, `<head>`, or `<body>`. A template that directly extends it will render an invalid HTML document, no CSS will be loaded from the host project's stylesheet, and the form will display unstyled in the browser.

You must **wrap the Gandalf template inside a host-project layout** that provides the full HTML skeleton. The idiomatic way is `{% embed %}`:

```twig
{# templates/admin/security/login.html.twig #}
{% extends 'admin/base_minimal.html.twig' %}  {# your full HTML layout #}

{% block title %}Login - My App{% endblock %}

{% block body %}
    {% embed '@_gandalf/security/login.html.twig' %}
        {% block logo %}
            <img src="/images/logo.svg" alt="My App">
        {% endblock %}

        {% block splash %}
            <img class="absolute inset-0 size-full object-cover"
                 src="/images/login-splash.jpg" alt="">
        {% endblock %}
    {% endembed %}
{% endblock %}
```

`admin/base_minimal.html.twig` is whatever minimal layout you have that emits `<!doctype>`, loads your admin CSS entry and opens `<body>`. The Gandalf fragment then lives inside `<body>`, so the host `<head>` already carries the stylesheet link.

## ⚠️ Important: project-global form themes may clobber `submit_widget`

If your host project registers a global form theme in `twig.yaml`:

```yaml
twig:
    form_themes:
        - 'front/form/form_theme.html.twig'
```

…and that global theme overrides the `submit_widget` block (e.g. to set `btn btn-primary`), the class passed by Gandalf's `submit_row` (`gandalf-field__submit`) gets silently **overwritten** — rendered buttons end up with the global classes instead of the Gandalf BEM class. The symptom is an unstyled submit button on the login page even though every other class is correct.

Fix: inside your login template, override the `main` block and apply a local form theme that re-declares `submit_widget`. Example:

```twig
{# templates/admin/security/_form_theme.html.twig #}
{% use '@_gandalf/security/form_theme.html.twig' %}

{% block submit_widget %}
    {%- set type = type|default('submit') -%}
    {%- set attr = attr|merge({'class': 'gandalf-field__submit'}) -%}
    {{- block('button_widget') -}}
{% endblock %}
```

```twig
{# templates/admin/security/login.html.twig — override the `main` block #}
{% block body %}
    {% embed '@_gandalf/security/login.html.twig' %}
        {% block logo %}...{% endblock %}
        {% block splash %}...{% endblock %}

        {% block main %}
            {% form_theme form 'security/_form_theme.html.twig' %}
            {{ form_start(form, { 'attr': { 'class': 'gandalf-login-form' } }) }}
                {% if error is defined and error %}
                    <div role="alert" class="gandalf-alert gandalf-alert--error">
                        {{ error.messageKey|trans(error.messageData, 'security') }}
                    </div>
                {% endif %}
                {{ form_rest(form) }}
            {{ form_end(form) }}
        {% endblock %}
    {% endembed %}
{% endblock %}
```

Why the re-render in `main`: `{% form_theme form '…' %}` applied before `{% embed %}` is not observed inside the embedded template's own `{% form_theme form _self %}` call. Overriding `main` lets you apply the theme on the exact instance that renders the form.

## Available templates

### `@_gandalf/security/layout.html.twig`

Authentication page layout (login, password reset). Two-column: form + splash. **Bare HTML fragment** — see the warning above.

| Block | Purpose |
|-------|---------|
| `meta` | Rendered before the layout root — intended for `<head>` additions but it falls in the document body when used without a host wrapper, so prefer putting `vite_entry_link_tags` in your host layout's `<head>` instead |
| `logo` | Brand logo image/text |
| `page_title` | Heading (h2) |
| `page_headline` | Sub-heading |
| `main` | Form content |
| `splash` | Right-side illustration |
| `footer` | Footer (scripts, links) |

CSS classes to style (full list):

| Class | Role |
|-------|------|
| `.gandalf-auth-layout` | Outer flex container |
| `.gandalf-auth-form` | Left column (form side) |
| `.gandalf-auth-form__inner` | Inner wrapper with max-width |
| `.gandalf-auth-form__header` | Logo + title + headline block |
| `.gandalf-auth-form__title` | `<h2>` element |
| `.gandalf-auth-form__headline` | Sub-heading `<p>` |
| `.gandalf-auth-form__content` | Form area (wraps the `main` block) |
| `.gandalf-auth-splash` | Right column (illustration) |

### `@_gandalf/security/login.html.twig`

Extends `layout.html.twig`. Renders the `LoginType` form with error handling via the `main` block.

Additional CSS classes:

| Class | Role |
|-------|------|
| `.gandalf-login-form` | The `<form>` element |
| `.gandalf-alert` | Alert container |
| `.gandalf-alert--error` | Error variant (bad credentials, expired CSRF, etc.) |

### `@_gandalf/security/form_theme.html.twig`

Form theme for authentication fields. Use via `{% use '@_gandalf/security/form_theme.html.twig' %}`.

CSS classes (full list):

| Class | Role |
|-------|------|
| `.gandalf-field` | Field wrapper (applied on every `form_row`) |
| `.gandalf-field__label` | Label element |
| `.gandalf-field__input` | Input container (wraps widget + errors) |
| `.gandalf-field__widget` | The input element itself |
| `.gandalf-field--submit` | Submit button wrapper (applied on `submit_row`) |
| `.gandalf-field__submit` | Submit button widget |

### `@_gandalf/admin/account/index.html.twig`

Account list. Uses `@_theme/layout/list.html.twig` from Cortex. Extends the `gandalf_admin_layout` Twig global (configurable via `gandalf.admin.layout`).

### `@_gandalf/admin/account/edit.html.twig`

ACL edit form with role checkboxes.

### `@_gandalf/admin/token/index.html.twig`

Token list for a single account with revoke action.

### `@_gandalf/admin/layout.html.twig`

Minimal fallback layout. Used only if the project doesn't configure `gandalf.admin.layout`.

## Integration example — CSS

Copy this into your project's admin stylesheet (e.g. `assets/src/styles/gandalf-admin.css`), adjust tokens to match your design system, and import it from your admin CSS entrypoint.

```css
/* Layout */
.gandalf-auth-layout {
    display: flex;
    min-height: 100vh;
}

.gandalf-auth-form {
    flex: 1 1 0%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 3rem 1rem;
}

@media (min-width: 640px)  { .gandalf-auth-form { padding: 3rem 1.5rem; } }
@media (min-width: 1024px) { .gandalf-auth-form { flex: none; padding: 3rem 5rem; } }
@media (min-width: 1280px) { .gandalf-auth-form { padding: 3rem 6rem; } }

.gandalf-auth-form__inner {
    width: 100%;
    max-width: 24rem;
    margin: 0 auto;
}
@media (min-width: 1024px) { .gandalf-auth-form__inner { width: 24rem; } }

.gandalf-auth-form__header {
    margin-bottom: 2.5rem;
}

.gandalf-auth-form__content {
    /* Intentionally empty: spacing comes from __header's margin-bottom. */
}

.gandalf-auth-form__title {
    margin-top: 2rem;
    font-size: 1.5rem;
    line-height: 2.25rem;
    font-weight: 700;
    color: var(--color-foreground);
}

.gandalf-auth-form__headline {
    margin-top: 0.5rem;
    font-size: 0.875rem;
    line-height: 1.5rem;
    color: var(--color-foreground);
}

.gandalf-auth-splash {
    position: relative;
    display: none;
    flex: 1 1 0%;
    width: 0;
}
@media (min-width: 1024px) { .gandalf-auth-splash { display: block; } }

/* Form fields */
.gandalf-login-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.gandalf-field {
    /* Intentionally empty: stacking comes from .gandalf-login-form's flex gap. */
}

.gandalf-field__label {
    display: block;
    font-size: 0.875rem;
    line-height: 1.5rem;
    font-weight: 500;
    color: var(--color-foreground);
}

.gandalf-field__input {
    margin-top: 0.5rem;
}

.gandalf-field__widget {
    display: block;
    width: 100%;
    appearance: none;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    background: var(--color-card);
    color: var(--color-foreground);
    border-radius: 0.375rem;
    outline: 1px solid var(--color-input);
    outline-offset: -1px;
}

.gandalf-field__widget::placeholder {
    color: var(--color-muted-foreground);
    font-style: italic;
}

.gandalf-field__widget:focus {
    outline: 2px solid var(--color-primary);
    outline-offset: -2px;
}

/* Submit */
.gandalf-field--submit {
    margin-top: 2rem;
}

.gandalf-field__submit {
    display: flex;
    width: 100%;
    justify-content: center;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    line-height: 1.5rem;
    font-weight: 600;
    color: white;
    background: var(--color-primary);
    border-radius: 0.375rem;
    border: none;
    cursor: pointer;
    transition: background-color 150ms ease-out;
}

.gandalf-field__submit:hover {
    background: color-mix(in srgb, var(--color-primary) 90%, black);
}

/* Alerts */
.gandalf-alert {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    line-height: 1.5rem;
    border-radius: 0.375rem;
    border: 1px solid transparent;
}

.gandalf-alert--error {
    color: var(--color-destructive);
    background: color-mix(in srgb, var(--color-destructive) 10%, transparent);
    border-color: color-mix(in srgb, var(--color-destructive) 30%, transparent);
}
```

## Translations

Gandalf ships translations in `fr` and `en` under the `gandalf` domain. Override them in your project:

```yaml
# translations/gandalf+intl-icu.fr.yaml
login.title: Connexion à Mon App
login.headline: Bienvenue sur votre espace
```
