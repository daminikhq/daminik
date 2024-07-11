<?php

declare(strict_types=1);

namespace App\Form;

use App\Dto\WorkspaceInvitation;
use App\Enum\UserRole;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkspaceInvitationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'label.email',
                'required' => false,
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'label.role',
                'required' => true,
                'choices' => [
                    'choice.role.user' => 'user',
                    'choice.role.admin' => 'admin',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'button.invite',
                'attr' => [
                    'class' => 'button',
                ],
            ]);

        $builder->get('role')
            ->addModelTransformer(
                new CallbackTransformer(
                    fn (?UserRole $roleAsEnum): ?string => $roleAsEnum?->value,
                    fn (?string $roleAsString): ?UserRole => match ($roleAsString) {
                        'user' => UserRole::WORKSPACE_USER,
                        'admin' => UserRole::WORKSPACE_ADMIN,
                        default => null
                    }
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WorkspaceInvitation::class,
            'translation_domain' => 'form',
        ]);
    }
}
