<?php

declare(strict_types=1);

namespace App\Form\File;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AiTagsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tags', ChoiceType::class, [
                'choices' => is_array($options['aiTags']) ? $this->mapTags($options['aiTags']) : [],
                'multiple' => true,
                'expanded' => true,
                'attr' => [
                    'class' => 'form__tags',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'button.saveUpdate',
                'attr' => [
                    'class' => 'button',
                ],
                'row_attr' => [
                    'class' => 'is-sticky',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'workspace' => null,
            'translation_domain' => 'form',
            'aiTags' => [],
        ]);
    }

    /**
     * @param array<string, array<string, string|float>> $aiTags
     *
     * @return array<string, string>
     */
    private function mapTags(array $aiTags): array
    {
        $choices = [];
        foreach ($aiTags as $aiTag) {
            $confidence = is_string($aiTag['confidence']) ? $aiTag['confidence'] : round($aiTag['confidence'], 2);
            $choices[$aiTag['tag'].' ('.$confidence.')'] = (string) $aiTag['tag'];
        }

        return $choices;
    }
}
