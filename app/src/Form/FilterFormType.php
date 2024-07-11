<?php

declare(strict_types=1);

namespace App\Form;

use App\Dto\Filter\FilterFormDto;
use App\Dto\Utility\FilterOptions;
use App\Enum\MimeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class FilterFormType extends AbstractType
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly RequestStack $requestStack
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $locale = $this->requestStack->getMainRequest()?->getLocale() ?? 'de';
        $dateFormatter = new \IntlDateFormatter(
            locale: $locale,
            dateType: \IntlDateFormatter::FULL,
            timeType: \IntlDateFormatter::FULL,
            pattern: 'LLLL yyyy'
        );

        $fileTypeChoices = [];
        /** @var FilterOptions|null $filterOptions */
        $filterOptions = $options['filterOptions'];
        $availableMimeTypes = $filterOptions?->getMimeTypes() ?? [];
        $allMimeTypes = MimeType::validCases();
        foreach ($allMimeTypes as $mimeType) {
            $fileTypeChoices[$mimeType->name] = strtolower($mimeType->name);
        }
        $monthChoices = [];
        $months = $filterOptions?->getMonths() ?? [];
        foreach ($months as $month) {
            $monthTitle = $dateFormatter->format($month->getDateTime());
            if (false !== $monthTitle) {
                $monthChoices[$monthTitle] = $month->getDateString();
            }
        }
        $builder
            ->add('filetype', ChoiceType::class, [
                'label' => 'label.fileTypes',
                'expanded' => true,
                'multiple' => true,
                'choices' => $fileTypeChoices,
                'attr' => [
                    'class' => 'is-filter-field filters__tag-group',
                ],
                'choice_attr' => function (string $value) use ($availableMimeTypes) {
                    if (is_array($availableMimeTypes) && !in_array(MimeType::tryFromName($value), $availableMimeTypes, true)) {
                        return ['disabled' => 'disabled'];
                    }

                    return [];
                },
            ])
            ->add('tags', TextType::class, [
                'label' => 'label.tags',
                'required' => false,
                'autocomplete' => true,
                'attr' => [
                    'placeholder' => 'label.choose',
                    'class' => 'is-filter-field',
                ],
                'tom_select_options' => [
                    'create' => true,
                    'createOnBlur' => true,
                    'delimiter' => ',',
                ],
                'autocomplete_url' => $this->router->generate('workspace_autocomplete_tags'),
            ])
            ->add('uploadedby', TextType::class, [
                'label' => 'label.uploadedBy',
                'required' => false,
                'autocomplete' => true,
                'attr' => [
                    'placeholder' => 'label.choose',
                    'class' => 'is-filter-field',
                ],
                'tom_select_options' => [
                    'create' => true,
                    'createOnBlur' => true,
                    'delimiter' => ',',
                ],
                'autocomplete_url' => $this->router->generate('workspace_autocomplete_users'),
            ])
            ->add('uploadedat', ChoiceType::class, [
                'label' => 'label.uploadDate',
                'placeholder' => 'label.choose',
                'required' => false,
                'attr' => [
                    'class' => 'is-filter-field',
                ],
                'choices' => $monthChoices,
                'expanded' => false,
                'multiple' => false,
            ])
            ->add('sort', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FilterFormDto::class,
            'workspace' => null,
            'translation_domain' => 'form',
            'filterOptions' => null,
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
