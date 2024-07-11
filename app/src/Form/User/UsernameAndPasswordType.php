<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class UsernameAndPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'label.username',
                'required' => true,
                'help' => 'help.username',
                'constraints' => [
                    new Regex('/^[a-z0-9-\.]+$/i', 'constraint.usernameRegex'),
                    new NotBlank([
                        'message' => 'constraint.usernameNotBlank',
                    ]),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'constraint.usernameLength',
                        // max length allowed by Symfony for security reasons
                        'max' => 180,
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'label' => 'label.password',
                'attr' => ['autocomplete' => 'new-password'],
                'help' => 'help.password',
                'constraints' => [
                    new NotBlank([
                        'message' => 'constraint.enterPassword',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'constraint.passwordLength',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('locale', ChoiceType::class, [
                'label' => 'label.locale',
                'required' => true,
                'choices' => [
                    'choice.locale.de' => 'de',
                    'choice.locale.en' => 'en',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'button.register',
                'attr' => [
                    'class' => 'button',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'translation_domain' => 'form',
        ]);
    }
}
