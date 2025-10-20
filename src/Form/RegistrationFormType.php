<?php

namespace App\Form;

use App\Entity\Usuario;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre',TextType::class,['label'=>'Full name'])
            ->add('email')
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'You should agree to our terms.',
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ])
        ;

        if ($options['show_partner_checkbox']) {
            $builder->add('solicitarPartner', CheckboxType::class, [
                'label' => 'Quiero ofrecer servicios como partner',
                'required' => false,
                'mapped' => false,
            ]);
        }

        if ($options['driver_mode']) {
            $builder
                ->add('driverDocumento', TextType::class, [
                    'label' => 'Documento',
                    'mapped' => false,
                    'constraints' => [
                        new NotBlank(['message' => 'Ingresa tu documento']),
                    ],
                ])
                ->add('driverTelefono', TextType::class, [
                    'label' => 'Teléfono de contacto',
                    'mapped' => false,
                    'constraints' => [
                        new NotBlank(['message' => 'Ingresa un teléfono de contacto']),
                    ],
                ])
                ->add('driverPatente', TextType::class, [
                    'label' => 'Patente del vehículo',
                    'mapped' => false,
                    'constraints' => [
                        new NotBlank(['message' => 'Ingresa la patente del vehículo']),
                    ],
                ])
                ->add('driverModeloVehiculo', TextType::class, [
                    'label' => 'Modelo del vehículo',
                    'mapped' => false,
                    'constraints' => [
                        new NotBlank(['message' => 'Ingresa el modelo del vehículo']),
                    ],
                ])
                ->add('driverNotas', TextareaType::class, [
                    'label' => 'Notas adicionales',
                    'mapped' => false,
                    'required' => false,
                    'attr' => ['rows' => 3],
                ])
                ->add('driverFoto', FileType::class, [
                    'label' => 'Foto del vehículo (JPG o PNG)',
                    'mapped' => false,
                    'required' => false,
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Usuario::class,
            'show_partner_checkbox' => true,
            'driver_mode' => false,
        ]);
        $resolver->setAllowedTypes('show_partner_checkbox', 'bool');
        $resolver->setAllowedTypes('driver_mode', 'bool');
    }
}
