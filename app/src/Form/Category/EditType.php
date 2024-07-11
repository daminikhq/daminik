<?php

declare(strict_types=1);

namespace App\Form\Category;

use App\Dto\Category\Edit;
use App\Entity\Workspace;
use App\Service\Workspace\WorkspaceIdentifier;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditType extends AbstractType
{
    public function __construct(
        private readonly WorkspaceIdentifier $workspaceIdentifier
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $workspace = $this->workspaceIdentifier->getWorkspace();
        if (!$workspace instanceof Workspace) {
            throw new \RuntimeException();
        }

        $builder
            ->add('title', TextType::class, [
                'label' => 'label.title',
            ])
            ->add('parent', CategoryTreeType::class, [
                'label' => 'label.parentCategory',
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('c')
                    ->andWhere('c.workspace = :workspace')
                    ->setParameter('workspace', $workspace)
                    ->orderBy('c.slug', 'ASC'),
                'placeholder' => 'label.selectOption',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'button.editCategory',
                'attr' => [
                    'class' => 'button',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Edit::class,
            'translation_domain' => 'form',
        ]);
    }
}
