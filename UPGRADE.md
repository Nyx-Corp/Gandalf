# Upgrade guide

## From project-owned login templates to `@_gandalf`

Gandalf now ships the login flow (`LoginType`, `LoginController`, `/login` route, Twig templates) under the `@_gandalf/security/*` namespace. Projects that previously carried their own `templates/admin/security/{_layout,_form_theme,login}.html.twig` with hard-coded Tailwind classes must migrate.

The **important** part of this migration is that **Gandalf templates ship with BEM classes but no CSS** — the classes have moved out of your project's template files, but the styling has **not** moved. If you don't also ship a stylesheet for the `gandalf-*` classes, the login page renders unstyled (splash image taking the full viewport, submit button as plain text, etc.).

### 1. Migration checklist

For each project that consumed the old login templates:

- [ ] Delete the project's old `templates/admin/security/_layout.html.twig`
- [ ] Delete any project-owned `login.html.twig` that duplicates form rendering
- [ ] Add `config/bundles.php` entry for `Gandalf\Bridge\Symfony\Bundle\GandalfBundle::class`
- [ ] Add `config/packages/gandalf.yaml` (see `docs/index.md`)
- [ ] Point the security routes at Gandalf in `config/routes/admin.yaml`:
      ```yaml
      _gandalf_security:
          resource: '@GandalfBundle/Resources/config/routes_security.yaml'
      ```
- [ ] Keep `templates/admin/security/login.html.twig` — but rewrite it as a thin wrapper (see template below)
- [ ] **Copy the CSS snippet from [`docs/templates.md`](docs/templates.md)** into a new stylesheet (e.g. `assets/src/styles/gandalf-admin.css`) and import it from your admin CSS entrypoint
- [ ] Rebuild the admin CSS bundle (`npm run build`) and deploy
- [ ] Smoke-test `/login`: the form must be two-column on wide viewports, the submit button must carry the `gandalf-field__submit` class and render with your primary color

### 2. Minimum login template

Your project's `templates/admin/security/login.html.twig` can be as short as:

```twig
{% extends 'admin/base_minimal.html.twig' %}

{% block title %}Connexion - My App Admin{% endblock %}

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

`admin/base_minimal.html.twig` is your project's minimal HTML skeleton (doctype, `<head>` with `vite_entry_link_tags('admin_css')`, open `<body>`). The Gandalf security layout is a **bare fragment** — it doesn't emit `<!doctype>`/`<html>`/`<head>`/`<body>`, so wrapping it in a host layout is mandatory.

### 3. The form-theme trap

If your project already registers a global form theme in `twig.yaml` that overrides `submit_widget` — e.g. a shared `front/form/form_theme.html.twig` that hard-codes `btn btn-primary` on submits — it will **clobber** the `gandalf-field__submit` class that `submit_row` from `@_gandalf/security/form_theme.html.twig` tries to apply. Symptom: the submit button on `/login` renders unstyled (plain text) even when the rest of the page is correctly themed.

The fix is a local theme override that re-declares `submit_widget` and is applied inside the `main` block of the embed:

**`templates/admin/security/_form_theme.html.twig`**
```twig
{% use '@_gandalf/security/form_theme.html.twig' %}

{% block submit_widget %}
    {%- set type = type|default('submit') -%}
    {%- set attr = attr|merge({'class': 'gandalf-field__submit'}) -%}
    {{- block('button_widget') -}}
{% endblock %}
```

**`templates/admin/security/login.html.twig`** — override the `main` block to re-render the form with the local theme:
```twig
{% block body %}
    {% embed '@_gandalf/security/login.html.twig' %}
        {% block logo %}…{% endblock %}
        {% block splash %}…{% endblock %}

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

You don't need this step if your project doesn't have a conflicting global form theme.

### 4. CSS classes reference

All `gandalf-*` classes used by `@_gandalf/security/*` templates:

**Layout** (`security/layout.html.twig`)
- `.gandalf-auth-layout`
- `.gandalf-auth-form`
- `.gandalf-auth-form__inner`
- `.gandalf-auth-form__header`
- `.gandalf-auth-form__title`
- `.gandalf-auth-form__headline`
- `.gandalf-auth-form__content`
- `.gandalf-auth-splash`

**Login page** (`security/login.html.twig`)
- `.gandalf-login-form`
- `.gandalf-alert`
- `.gandalf-alert--error`

**Form theme** (`security/form_theme.html.twig`)
- `.gandalf-field`
- `.gandalf-field__label`
- `.gandalf-field__input`
- `.gandalf-field__widget`
- `.gandalf-field--submit`
- `.gandalf-field__submit`

A CSS implementation (BridgeIt design tokens) lives in `docs/templates.md`. Copy it, swap tokens for your own, and you're done.

### 5. Smoke test

After the upgrade, run the login page in a browser and verify:

- Two-column layout on viewports ≥ 1024px (form left, splash right)
- Single-column stacked layout on narrow viewports (splash hidden)
- Inputs have padding, border, focus outline
- Submit button has your primary background color
- Bad credentials trigger the `.gandalf-alert--error` block with visible red styling
- Page source contains a proper `<!doctype html>`, `<html>`, `<head>`, `<body>` — not a bare `<div class="gandalf-auth-layout">` at the top

If any of those fail, re-check the CSS import and re-read the two ⚠️ warnings in `docs/templates.md`.
