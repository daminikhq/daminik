<?php

declare(strict_types=1);

namespace App\Form\Collection;

use App\Dto\Collection\Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'label.title',
            ])
            ->add('slug', TextType::class, [
                'label' => 'label.slug',
                'help' => 'constraint.slugWarning',
            ])
            ->add('public', CheckboxType::class, [
                'label' => 'label.public',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'button.saveUpdate',
                'attr' => [
                    'class' => 'button',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Config::class,
            'translation_domain' => 'form',
        ]);
    }
}
