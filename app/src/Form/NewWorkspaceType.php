<?php

namespace App\Form;

use App\Dto\NewWorkspace;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewWorkspaceType extends AbstractType
{
    public function __construct(private readonly string $domain)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'label.name',
            ])
            ->add('slug', TextType::class, [
                'label' => 'label.slug',
                'attr' => [
                    'class' => 'has-daminik-domain',
                    'data-domain' => '.'.$this->domain,
                ],
            ])
            ->add('locale', ChoiceType::class, [
                'label' => 'label.locale',
                'required' => true,
                'choices' => [
                    'choice.locale.de' => 'de',
                    'choice.locale.en' => 'en',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'button.createWorkspace',
                'attr' => [
                    'class' => 'button',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NewWorkspace::class,
            'translation_domain' => 'form',
        ]);
    }
}
