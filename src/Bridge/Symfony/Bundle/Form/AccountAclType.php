<?php

declare(strict_types=1);

namespace Gandalf\Bridge\Symfony\Bundle\Form;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<mixed>
 */
class AccountAclType extends AbstractType
{
    public function __construct(
        private readonly ParameterBagInterface $params,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var array<string, string> $roles */
        $roles = $this->params->get('gandalf.admin.roles');

        $builder
            ->add('acl', ChoiceType::class, [
                'label' => 'account.edit.acl',
                'choices' => array_flip($roles),
                'expanded' => true,
                'multiple' => true,
                'translation_domain' => 'gandalf',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'account.edit.save',
                'translation_domain' => 'gandalf',
            ])
        ;
    }
}
