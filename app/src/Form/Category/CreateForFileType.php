<?php

declare(strict_types=1);

namespace App\Form\Category;

use App\Dto\Category\Create;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateForFileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'label.title',
            ])
            ->add('parent', CategoryTreeType::class, [
                'label' => 'label.parentCategory',
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('c')
                    ->andWhere('c.workspace = :workspace')
                    ->setParameter('workspace', $options['workspace'])
                    ->orderBy('c.slug', 'ASC'),
                'placeholder' => 'label.select',
                'required' => false,
                'multiple' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'button.createCategory',
                'attr' => [
                    'class' => 'button',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Create::class,
            'translation_domain' => 'form',
            'workspace' => null,
        ]);
    }
}
