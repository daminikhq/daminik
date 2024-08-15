<?php

declare(strict_types=1);

namespace App\Form\File;

use App\Dto\File\Edit;
use App\Entity\AssetCollection;
use App\Entity\Workspace;
use App\Form\Category\CategoryTreeType;
use App\Security\Voter\WorkspaceVoter;
use App\Service\Collection\CollectionHandlerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class EditType extends AbstractType
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly CollectionHandlerInterface $collectionHandler,
        private readonly Security $security
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $workspace = $options['workspace'];
        $collectionChoices = $collections = [];
        $canEdit = false;
        if ($workspace instanceof Workspace) {
            $collections = $this->collectionHandler->getWorkspaceCollections($workspace);
            foreach ($collections as $collection) {
                $collectionChoices[$collection->getTitle()] = $collection->getId();
            }
            if ($this->security->isGranted(WorkspaceVoter::EDIT_ASSET, $workspace)) {
                $canEdit = true;
            }
        }
        $builder
            ->add('title', TextType::class, [
                'label' => 'label.title',
                'required' => false,
                'attr' => [
                    'placeholder' => 'placeholder.title',
                    'readonly' => !$canEdit,
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'label.description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'placeholder.description',
                    'readonly' => !$canEdit,
                ],
            ])
            ->add('public', CheckboxType::class, [
                'label' => 'label.cdn',
                'required' => false,
                'disabled' => !$canEdit,
            ])
            ->add('tags', TextType::class, [
                'label' => 'label.tags',
                'required' => false,
                'autocomplete' => $canEdit,
                'tom_select_options' => [
                    'create' => true,
                    'createOnBlur' => true,
                    'delimiter' => ',',
                ],
                'autocomplete_url' => $this->router->generate('workspace_autocomplete_tags'),
                'attr' => [
                    'readonly' => !$canEdit,
                ],
            ])
            ->add('category', CategoryTreeType::class, [
                'label' => 'label.directory',
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('c')
                    ->andWhere('c.workspace = :workspace')
                    ->setParameter('workspace', $options['workspace'])
                    ->orderBy('c.slug', 'ASC'),
                'placeholder' => 'label.select',
                'required' => false,
                'multiple' => false,
                'attr' => [
                    'readonly' => !$canEdit,
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'button.saveUpdate',
                'attr' => [
                    'class' => 'button',
                    'disabled' => !$canEdit,
                ],
            ]);
        $builder
            ->add('assetCollections', ChoiceType::class, [
                'label' => 'label.collections',
                'placeholder' => 'label.select',
                'choices' => $collectionChoices,
                'required' => false,
                'multiple' => true,
                'attr' => [
                    'readonly' => !$canEdit,
                    'data-controller' => 'assetview-collection-autocomplete',
                ],
                'autocomplete' => true,
            ]);
        $builder->get('assetCollections')
            ->addModelTransformer(
                new CallbackTransformer(
                    function ($assetCollections) {
                        $pickedCollections = [];
                        /** @var AssetCollection $collection */
                        foreach ($assetCollections as $collection) {
                            $pickedCollections[] = $collection->getId();
                        }

                        return $pickedCollections;
                    },
                    function (array $selection) use ($collections): array {
                        $pickedCollections = [];
                        foreach ($collections as $collection) {
                            if (in_array($collection->getId(), $selection, true)) {
                                $pickedCollections[] = $collection;
                            }
                        }

                        return $pickedCollections;
                    }
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Edit::class,
            'workspace' => null,
            'translation_domain' => 'form',
        ]);
    }
}
