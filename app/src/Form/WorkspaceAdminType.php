<?php

declare(strict_types=1);

namespace App\Form;

use App\Dto\WorkspaceAdmin;
use App\Enum\MimeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class WorkspaceAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $logo = $options['logo'] ?? false;

        $builder
            ->add('name', TextType::class, [
                'label' => 'label.name',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a name',
                    ]),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'Your name should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 180,
                    ]),
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
            ->add('apiAccess', CheckboxType::class, [
                'label' => 'label.apiAccess',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'button.saveUpdate',
                'attr' => [
                    'class' => 'button',
                ],
            ])
            ->add('logo', FileType::class, [
                'label' => 'label.logo',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'accept' => is_array($options['mimeTypes']) ? implode(', ', $options['mimeTypes']) : null,
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '51200k',
                        'mimeTypes' => $options['mimeTypes'],
                        'mimeTypesMessage' => 'constraint.validFile',
                    ]),
                ],
            ]);

        if ($logo) {
            $builder->add('resetLogo', SubmitType::class, [
                'label' => 'button.resetLogo',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WorkspaceAdmin::class,
            'mimeTypes' => [
                MimeType::JPG->value,
                MimeType::PNG->value,
                MimeType::SVG->value,
                MimeType::GIF->value,
            ],
            'translation_domain' => 'form',
            'logo' => false,
        ]);
    }
}
