<?php

declare(strict_types=1);

namespace Gandalf\Tests\Unit\Bridge\Symfony\Bundle\Form;

use Gandalf\Bridge\Symfony\Bundle\Form\LoginType;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Validator\Validation;

#[CoversClass(LoginType::class)]
class LoginTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        return [
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    public function testFormHasUsernameField(): void
    {
        $form = $this->factory->create(LoginType::class);

        $this->assertTrue($form->has('username'));
    }

    public function testFormHasPasswordField(): void
    {
        $form = $this->factory->create(LoginType::class);

        $this->assertTrue($form->has('password'));
    }

    public function testFormHasSubmitButton(): void
    {
        $form = $this->factory->create(LoginType::class);

        $this->assertTrue($form->has('submit'));
    }

    public function testFormSubmitWithValidData(): void
    {
        $form = $this->factory->create(LoginType::class);

        $form->submit([
            'username' => 'user@example.com',
            'password' => 'secret',
        ]);

        $this->assertTrue($form->isSynchronized());
        $data = $form->getData();
        $this->assertSame('user@example.com', $data['username']);
        $this->assertSame('secret', $data['password']);
    }

    public function testUsernameFieldHasEmailAutocomplete(): void
    {
        $form = $this->factory->create(LoginType::class);
        $view = $form->createView();

        $this->assertSame('email', $view->children['username']->vars['attr']['autocomplete']);
    }

    public function testPasswordFieldHasCurrentPasswordAutocomplete(): void
    {
        $form = $this->factory->create(LoginType::class);
        $view = $form->createView();

        $this->assertSame('current-password', $view->children['password']->vars['attr']['autocomplete']);
    }

    public function testFieldsUseGandalfTranslationDomain(): void
    {
        $form = $this->factory->create(LoginType::class);
        $view = $form->createView();

        $this->assertSame('gandalf', $view->children['username']->vars['translation_domain']);
        $this->assertSame('gandalf', $view->children['password']->vars['translation_domain']);
        $this->assertSame('gandalf', $view->children['submit']->vars['translation_domain']);
    }
}
