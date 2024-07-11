<?php

declare(strict_types=1);

namespace App\Form\File;

use App\Entity\File;
use App\Enum\HandleDeleteAction;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HandleDeletedType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(HandleDeleteAction::UNDELETE->value, SubmitType::class, [
                'label' => 'button.undelete',
                'attr' => [
                    'class' => 'button',
                ],
            ])
            ->add(HandleDeleteAction::DELETE->value, SubmitType::class, [
                'label' => 'button.delete',
                'attr' => [
                    'class' => 'button',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => File::class,
            'translation_domain' => 'form',
        ]);
    }
}
