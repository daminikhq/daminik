<?php

declare(strict_types=1);

namespace App\Form\File;

use App\Dto\File\Upload;
use App\Enum\MimeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class UploadType extends AbstractType
{
    public function __construct(
        private readonly RouterInterface $router
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setAction($this->router->generate('workspace_upload_index'))
        ;
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
