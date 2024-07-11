<?php

declare(strict_types=1);

namespace App\Form\Category;

use App\Entity\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryTreeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'parent_method_name' => 'getParent',
            'children_method_name' => 'getChildren',
            'prefix' => '-',
            'class' => Category::class,
        ]);
    }

    /**
     * @param array<string, string> $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $choices = [];

        $parent_method_name = $options['parent_method_name'];
        foreach ($view->vars['choices'] as $choice) {
            if (null === $choice->data->$parent_method_name()) {
                $choices[$choice->value] = $choice->data;
            }
        }

        $choices = $this->buildTreeChoices($choices, $options);

        $view->vars['choices'] = $choices;
    }

    /**
     * @param object[]              $choices
     * @param array<string, string> $options
     *
     * @return array<int|string, ChoiceView>
     */
    protected function buildTreeChoices(array $choices, array $options, int $level = 0): array
    {
        $result = [];
        $children_method_name = $options['children_method_name'];

        /** @var Category $choice */
        foreach ($choices as $choice) {
            $choiceId = $choice->getId();
            $result[$choiceId] = new ChoiceView(
                $choice,
                (string) $choiceId,
                str_repeat($options['prefix'], $level).' '.$choice->getTitle(),
                []
            );

            if (!$choice->$children_method_name()->isEmpty()) {
                $result += $this->buildTreeChoices($choice->$children_method_name()->toArray(), $options, $level + 1);
            }
        }

        return $result;
    }

    public function getParent(): ?string
    {
        return EntityType::class;
    }
}
