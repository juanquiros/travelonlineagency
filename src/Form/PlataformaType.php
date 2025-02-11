<?php

namespace App\Form;


use App\Entity\Lenguaje;
use App\Entity\Moneda;
use App\Entity\Plataforma;
use PayPalHttp\Serializer\Text;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlataformaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre',TextType::class,['label'=>'Nombre plataforma'])
            ->add('traslados_OD_libres',CheckboxType::class,['label'=>'Permitir traslados origen destino libre','required'=>false])
            ->add('tasa_traslados_def',NumberType::class,['label'=>'Comisión Plataforma por traslados'])
            ->add('logo', FileType::class,[
                "data_class" => null,
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'image/*',
                        ],
                        'mimeTypesMessage' => 'solo imagenes',
                    ])
                ]])
            ->add('icono', FileType::class,[
                "data_class" => null,
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'image/*',
                        ],
                        'mimeTypesMessage' => 'solo imagenes',
                    ])
                ]])
            ->add('language_def', EntityType::class, [
                'class' => Lenguaje::class,
                'choice_label' => 'nombre',
            ])
            ->add('moneda_def', EntityType::class, [
                'class' => Moneda::class,
                'choice_label' => 'nombre',
            ])
            ->add('linkInstagram',TextType::class,['label'=>'Link Instagram',
                'required' => false])
            ->add('linkWhatsapp',TextType::class,['label'=>'Link Whatsapp',
                'required' => false])
            ->add('contactoTelefono',TextType::class,['label'=>'Telefono de contacto',
                'required' => false])
            ->add('contactoCorreo',TextType::class,['label'=>'Correo de contacto',
                'required' => false])
            ->add('contactoDireccion',TextType::class,['label'=>'Dirección para mostrar',
                'required' => false])
            ->add('Guardar',SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Plataforma::class,
        ]);
    }
}
