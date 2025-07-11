<?php

namespace App\Form;

use App\Entity\Vehicle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class VehicleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('brand', TextType::class, [
                'label' => 'Marque',
                'attr' => [
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent focus:border-accent transition-all duration-300',
                    'placeholder' => 'Ex: Peugeot, Renault, Tesla...'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La marque est obligatoire']),
                    new Assert\Length(['max' => 100, 'maxMessage' => 'La marque ne peut pas dépasser {{ limit }} caractères'])
                ]
            ])
            ->add('model', TextType::class, [
                'label' => 'Modèle',
                'attr' => [
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent focus:border-accent transition-all duration-300',
                    'placeholder' => 'Ex: 208, Clio, Model 3...'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le modèle est obligatoire']),
                    new Assert\Length(['max' => 100, 'maxMessage' => 'Le modèle ne peut pas dépasser {{ limit }} caractères'])
                ]
            ])
            ->add('plate', TextType::class, [
                'label' => 'Plaque d\'immatriculation',
                'attr' => [
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent focus:border-accent transition-all duration-300',
                    'placeholder' => 'Ex: AB-123-CD',
                    'style' => 'text-transform: uppercase'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La plaque d\'immatriculation est obligatoire']),
                    new Assert\Length(['max' => 20, 'maxMessage' => 'La plaque ne peut pas dépasser {{ limit }} caractères'])
                ]
            ])
            ->add('energy', ChoiceType::class, [
                'label' => 'Type d\'énergie',
                'choices' => [
                    'Essence' => 'Essence',
                    'Diesel' => 'Diesel',
                    'Électrique' => 'Électrique',
                    'Hybride' => 'Hybride',
                    'GPL' => 'GPL',
                    'Autre' => 'Autre'
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent focus:border-accent transition-all duration-300'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le type d\'énergie est obligatoire'])
                ]
            ])
            ->add('color', ChoiceType::class, [
                'label' => 'Couleur',
                'choices' => [
                    'Blanc' => 'Blanc',
                    'Noir' => 'Noir',
                    'Gris' => 'Gris',
                    'Rouge' => 'Rouge',
                    'Bleu' => 'Bleu',
                    'Vert' => 'Vert',
                    'Jaune' => 'Jaune',
                    'Orange' => 'Orange',
                    'Violet' => 'Violet',
                    'Marron' => 'Marron',
                    'Beige' => 'Beige',
                    'Argenté' => 'Argenté',
                    'Autre' => 'Autre'
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent focus:border-accent transition-all duration-300'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La couleur est obligatoire'])
                ]
            ])
            ->add('seats', IntegerType::class, [
                'label' => 'Nombre de places',
                'attr' => [
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent focus:border-accent transition-all duration-300',
                    'min' => 2,
                    'max' => 8,
                    'placeholder' => 'Ex: 5'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nombre de places est obligatoire']),
                    new Assert\Range(['min' => 2, 'max' => 8, 'notInRangeMessage' => 'Le nombre de places doit être entre {{ min }} et {{ max }}'])
                ]
            ])
            ->add('registrationDate', DateType::class, [
                'label' => 'Date de première immatriculation',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent focus:border-accent transition-all duration-300'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date d\'immatriculation est obligatoire']),
                    new Assert\LessThanOrEqual(['value' => 'today', 'message' => 'La date d\'immatriculation ne peut pas être dans le futur'])
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Ajouter le véhicule',
                'attr' => [
                    'class' => 'w-full bg-accent hover:bg-accentDark text-textPrimary font-base py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vehicle::class,
        ]);
    }
} 