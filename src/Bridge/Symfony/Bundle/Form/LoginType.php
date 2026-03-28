<?php

declare(strict_types=1);

namespace Gandalf\Bridge\Symfony\Bundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class LoginType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'authenticate',
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', EmailType::class, [
                'label' => 'login.email_label',
                'required' => true,
                'attr' => [
                    'placeholder' => 'votre@email.fr',
                    'autocomplete' => 'email',
                ],
                'translation_domain' => 'gandalf',
            ])
            ->add('password', PasswordType::class, [
                'label' => 'login.password_label',
                'required' => true,
                'attr' => [
                    'placeholder' => '********',
                    'autocomplete' => 'current-password',
                ],
                'translation_domain' => 'gandalf',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'login.submit',
                'translation_domain' => 'gandalf',
            ])
        ;
    }
}
