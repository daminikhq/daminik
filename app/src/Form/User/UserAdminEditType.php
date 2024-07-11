<?php

namespace App\Form\User;

use App\Dto\User\UserAdminEdit;
use App\Enum\UserRole;
use App\Security\Voter\WorkspaceVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserAdminEditType extends AbstractType
{
    public function __construct(
        private readonly Security $security
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('role', ChoiceType::class, [
                'label' => 'label.role',
                'required' => true,
                'choices' => $this->getChoices(),
            ])
            ->add('submit', SubmitType::class);

        $builder->get('role')
            ->addModelTransformer(
                new CallbackTransformer(
                    fn (?UserRole $roleAsEnum): ?string => $roleAsEnum?->value,
                    function (?string $roleAsString): UserRole {
                        if (null === $roleAsString) {
                            return UserRole::WORKSPACE_USER;
                        }

                        return UserRole::tryFrom($roleAsString) ?? UserRole::WORKSPACE_USER;
                    }
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserAdminEdit::class,
            'translation_domain' => 'form',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function getChoices(): array
    {
        $choices = [
            // 'choice.role.viewer' => UserRole::WORKSPACE_VIEWER->value,
            'choice.role.user' => UserRole::WORKSPACE_USER->value,
        ];

        if ($this->security->isGranted(WorkspaceVoter::ADMINS_EDIT)) {
            $choices['choice.role.admin'] = UserRole::WORKSPACE_ADMIN->value;
        }

        if ($this->security->isGranted(WorkspaceVoter::OWNERS_EDIT)) {
            $choices['choice.role.owner'] = UserRole::WORKSPACE_OWNER->value;
        }

        return $choices;
    }
}
