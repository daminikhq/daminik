<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Dto\Admin\Form\UserEdit;
use App\Enum\UserRole;
use App\Enum\UserStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('status', ChoiceType::class, [
                'label' => 'label.status',
                'required' => true,
                'choices' => UserStatus::getChoices(),
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'label.role',
                'required' => true,
                'choices' => UserRole::getGlobalChoices(),
            ])
            ->add('adminNotice', TextType::class, [
                'label' => 'label.adminNotice',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'button.editUser',
                'attr' => [
                    'class' => 'button',
                ],
            ]);

        $builder
            ->get('status')
            ->addModelTransformer(new CallbackTransformer(
                transform: fn (UserStatus $status): string => $status->value,
                reverseTransform: fn (string $statusValue): UserStatus => UserStatus::tryFrom($statusValue) ?? UserStatus::ACTIVE
            ));
        $builder
            ->get('role')
            ->addModelTransformer(new CallbackTransformer(
                transform: fn (UserRole $role): string => $role->value,
                reverseTransform: fn (string $roleValue): UserRole => UserRole::tryFrom($roleValue) ?? UserRole::USER
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserEdit::class,
            'translation_domain' => 'form',
        ]);
    }
}
