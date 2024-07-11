<?php

declare(strict_types=1);

namespace App\Form;

use App\Dto\Filter\SearchFormDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class SearchFormType extends AbstractType
{
    public function __construct(
        private readonly RouterInterface $router
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $slug = null;
        if (
            array_key_exists('data', $options)
            && $options['data'] instanceof SearchFormDto
        ) {
            $slug = $options['data']->getSlug();
        }

        $inFilterForm = false;
        if (
            array_key_exists('inFilterForm', $options)
            && is_bool($options['inFilterForm'])
        ) {
            $inFilterForm = $options['inFilterForm'];
        }

        $searchAttributes = [];
        if ($inFilterForm) {
            $searchAttributes['form'] = 'filters-form';
            $builder->add('s', SearchType::class, [
                'attr' => [
                    'form' => 'filters-form',
                ],
            ]);
        }
        $builder->add('s', SearchType::class, [
            'attr' => $searchAttributes,
        ])
            ->add('parameters', HiddenType::class)
            ->add('route', HiddenType::class);
        if (null !== $slug) {
            $builder->add('slug', HiddenType::class);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchFormDto::class,
            'workspace' => null,
            'translation_domain' => 'form',
            'availableMimeTypes' => [],
            'csrf_protection' => false,
            'action' => $this->router->generate('workspace_search'),
            'method' => 'GET',
            'inFilterForm' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
