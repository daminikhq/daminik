<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Dto\Admin\Form\WorkspaceEdit;
use App\Enum\WorkspaceStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkspaceEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('status', ChoiceType::class, [
                'label' => 'label.status',
                'required' => true,
                'choices' => WorkspaceStatus::getChoices(),
            ])
            ->add('adminNotice', TextType::class, [
                'label' => 'label.adminNotice',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'button.editWorkspace',
                'attr' => [
                    'class' => 'button',
                ],
            ]);

        $builder
            ->get('status')
            ->addModelTransformer(new CallbackTransformer(
                transform: fn (WorkspaceStatus $status): string => $status->value,
                reverseTransform: fn (string $statusValue): WorkspaceStatus => WorkspaceStatus::tryFrom($statusValue) ?? WorkspaceStatus::ACTIVE
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WorkspaceEdit::class,
            'translation_domain' => 'form',
        ]);
    }
}
