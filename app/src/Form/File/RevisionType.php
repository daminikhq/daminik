<?php

declare(strict_types=1);

namespace App\Form\File;

use App\Dto\File\Revision;
use App\Enum\MimeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class RevisionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $mimeTypes = [
            MimeType::JPG->value,
            MimeType::PNG->value,
            MimeType::SVG->value,
            MimeType::GIF->value,
        ];

        $data = $options['data'];
        if (($data instanceof Revision) && null !== $data->getFile()->getMime()) {
            $mimeTypes = [$data->getFile()->getMime()];
        }

        $builder
            ->add('file', FileType::class, [
                'label' => 'label.file',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(),
                    new File([
                        'maxSize' => '51200k',
                        'mimeTypes' => $mimeTypes,
                        'mimeTypesMessage' => 'constraint.validFile',
                    ]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'button.upload',
                'attr' => [
                    'class' => 'button',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Revision::class,
            'translation_domain' => 'form',
        ]);
    }
}
