<?php

namespace App\Form;

use App\Entity\User;
use App\Service\User\RegistrationCodeHandler;
use App\Validator\IsValidRegistrationCode;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function __construct(
        private readonly RegistrationCodeHandler $registrationCodeHandler,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'label.email',
                'attr' => [
                    'autocomplete' => 'email',
                ],
            ]);

        if (
            false === $options['invitation']
            && $this->registrationCodeHandler->hasActiveRegistrationCodes()
        ) {
            $constraints = [];

            if ($this->registrationCodeHandler->needsCode()) {
                $constraints[] = new NotBlank([
                    'message' => 'constraint.enterCode',
                ]);
            }

            $constraints[] = new IsValidRegistrationCode([
                'message' => 'constraint.wrongCode',
            ]);
            $builder
                ->add('inviteCode', TextType::class, [
                    'label' => 'label.inviteCode',
                    'mapped' => false,
                    'required' => $this->registrationCodeHandler->needsCode(),
                    'constraints' => $constraints,
                ]);
            if (is_string($options['inviteCode'])) {
                $builder->get('inviteCode')->setData($options['inviteCode']);
            }
        }

        $builder
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
            'invitation' => false,
            'inviteCode' => null,
            'translation_domain' => 'form',
        ]);
    }
}
