<?php

declare(strict_types=1);

namespace Gandalf\Bridge\Symfony\Bundle\Form;

use Gandalf\Component\Security\Enum\ProviderType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CredentialEditType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'gandalf',
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $providerChoices = [];
        foreach (ProviderType::cases() as $provider) {
            $providerChoices['credential.provider.' . $provider->value] = $provider->value;
        }

        $builder
            ->add('name', Form\TextType::class, [
                'label' => 'credential.fields.name.label',
                'help' => 'credential.fields.name.help',
            ])
            ->add('provider', Form\ChoiceType::class, [
                'label' => 'credential.fields.provider.label',
                'choices' => $providerChoices,
            ])
            ->add('data', Form\TextareaType::class, [
                'label' => 'credential.fields.data.label',
                'help' => 'credential.fields.data.help',
                'required' => false,
                'attr' => ['rows' => 8, 'class' => 'font-mono text-sm'],
            ])
        ;
    }
}
