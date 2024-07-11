<?php

declare(strict_types=1);

namespace App\Form\File;

use App\Dto\File\Upload;
use App\Enum\MimeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class LogoUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                child: 'file',
                type: FileType::class,
                options: [
                    'label' => 'label.file',
                    'mapped' => false,
                    'attr' => [
                        'class' => 'uppy',
                        'multiple' => 'true',
                        'placeholder' => 'placeholder.draganddrop',
                        'accept' => is_array($options['mimeTypes']) ? implode(', ', $options['mimeTypes']) : null,
                    ],
                    'constraints' => [
                        new NotBlank(),
                        new File([
                            'maxSize' => '51200k',
                            'mimeTypes' => $options['mimeTypes'],
                            'mimeTypesMessage' => 'constraint.validFile',
                        ]),
                    ],
                ]
            )
            ->add('submit', SubmitType::class, [
                'label' => 'button.uploadLogo',
                'attr' => [
                    'class' => 'button',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Upload::class,
            'mimeTypes' => [
                MimeType::JPG->value,
                MimeType::PNG->value,
                MimeType::SVG->value,
                MimeType::GIF->value,
            ],
            'translation_domain' => 'form',
        ]);
    }
}
