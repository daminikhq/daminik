<?php

namespace App\Form\File;

use App\Dto\File\MultiAction;
use App\Entity\Workspace;
use App\Form\Category\CategoryTreeType;
use App\Service\Collection\CollectionHandlerInterface;
use App\Service\Workspace\WorkspaceIdentifier;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MultiActionType extends AbstractType
{
    public function __construct(
        private readonly WorkspaceIdentifier $workspaceIdentifier,
        private readonly CollectionHandlerInterface $collectionHandler
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $multiAction = $options['data'];
        $files = ($multiAction instanceof MultiAction) ? $multiAction->getFiles() : [];
        $actionEnum = ($multiAction instanceof MultiAction) ? $multiAction->getAction() : [];
        $workspace = $this->workspaceIdentifier->getWorkspace();

        $builder
            ->add('action', HiddenType::class)
            ->add('files', ChoiceType::class, [
                'label' => 'label.files',
                'choices' => $files,
                'choice_name' => 'id',
                'choice_value' => 'filename',
                'choice_label' => 'filename',
                'expanded' => true,
                'multiple' => true,
            ]);
        if (\App\Enum\MultiAction::COLLECTION_ADD === $actionEnum) {
            $collections = $workspace instanceof Workspace ? $this->collectionHandler->getWorkspaceCollections($workspace) : [];
            $builder->add('collection', ChoiceType::class, [
                'label' => 'label.collection',
                'choices' => $collections,
                'placeholder' => 'label.selectOption',
                'choice_value' => 'id',
                'choice_label' => 'title',
            ]);
        }

        if (\App\Enum\MultiAction::CATEGORY_ADD === $actionEnum) {
            $builder->add('category', CategoryTreeType::class, [
                'label' => 'label.directory',
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('c')
                    ->andWhere('c.workspace = :workspace')
                    ->setParameter('workspace', $workspace)
                    ->orderBy('c.slug', 'ASC'),
                'placeholder' => 'label.selectOption',
                'required' => false,
                'multiple' => false,
            ]);
        }

        $builder->add('submit', SubmitType::class, [
            'label' => 'button.saveUpdate',
            'attr' => [
                'class' => 'button',
            ],
        ]);

        $builder->get('action')->addModelTransformer(
            modelTransformer: new CallbackTransformer(
                transform: fn (\App\Enum\MultiAction $actionAsEnum): string => $actionAsEnum->value,
                reverseTransform: fn (string $actionAsString): ?\App\Enum\MultiAction => \App\Enum\MultiAction::tryFrom($actionAsString)
            )
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MultiAction::class,
            'translation_domain' => 'form',
        ]);
    }
}
