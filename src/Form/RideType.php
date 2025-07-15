<?php

namespace App\Form;

use App\Entity\Ride;
use App\Entity\Vehicle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class RideType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];
        
        $builder
            ->add('departure', TextType::class, [
                'label' => 'Ville de départ',
                'attr' => [
                    'id' => 'ride_departure',
                    'placeholder' => 'Ex: Paris, Lyon, Marseille...',
                    'autocomplete' => 'off',
                    'class' => 'w-full px-3 py-2 bg-white/20 border border-white/30 rounded-lg text-backgroundDark placeholder-backgroundDark/60 focus:outline-none focus:border-accent backdrop-blur-sm'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La ville de départ est obligatoire']),
                    new Assert\Length(['max' => 255, 'maxMessage' => 'La ville de départ ne peut pas dépasser {{ limit }} caractères'])
                ]
            ])
            ->add('arrival', TextType::class, [
                'label' => 'Ville d\'arrivée',
                'attr' => [
                    'id' => 'ride_arrival',
                    'placeholder' => 'Ex: Paris, Lyon, Marseille...',
                    'autocomplete' => 'off',
                    'class' => 'w-full px-3 py-2 bg-white/20 border border-white/30 rounded-lg text-backgroundDark placeholder-backgroundDark/60 focus:outline-none focus:border-accent backdrop-blur-sm'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La ville d\'arrivée est obligatoire']),
                    new Assert\Length(['max' => 255, 'maxMessage' => 'La ville d\'arrivée ne peut pas dépasser {{ limit }} caractères'])
                ]
            ])
            ->add('date', DateType::class, [
                'label' => 'Date du voyage',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'w-full px-3 py-2 bg-white/20 border border-white/30 rounded-lg text-backgroundDark focus:outline-none focus:border-accent backdrop-blur-sm'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date du voyage est obligatoire']),
                    new Assert\GreaterThanOrEqual(['value' => 'today', 'message' => 'La date du voyage ne peut pas être dans le passé'])
                ]
            ])
            ->add('departureTime', TimeType::class, [
                'label' => 'Heure de départ',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'w-full px-3 py-2 bg-white/20 border border-white/30 rounded-lg text-backgroundDark focus:outline-none focus:border-accent backdrop-blur-sm'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'heure de départ est obligatoire'])
                ]
            ])
            ->add('arrivalTime', TimeType::class, [
                'label' => 'Heure d\'arrivée (estimation)',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'w-full px-3 py-2 bg-white/20 border border-white/30 rounded-lg text-backgroundDark focus:outline-none focus:border-accent backdrop-blur-sm'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'heure d\'arrivée est obligatoire'])
                ]
            ])
            ->add('price', IntegerType::class, [
                'label' => 'Prix par passager (crédits)',
                'attr' => [
                    'placeholder' => 'Ex: 5',
                    'min' => 1,
                    'max' => 50,
                    'class' => 'w-full px-3 py-2 bg-white/20 border border-white/30 rounded-lg text-backgroundDark placeholder-backgroundDark/60 focus:outline-none focus:border-accent backdrop-blur-sm'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prix est obligatoire']),
                    new Assert\Range(['min' => 1, 'max' => 50, 'notInRangeMessage' => 'Le prix doit être entre {{ min }} et {{ max }} crédits'])
                ]
            ])
            ->add('availableSeats', IntegerType::class, [
                'label' => 'Places disponibles',
                'attr' => [
                    'min' => 1,
                    'max' => 7,
                    'placeholder' => 'Ex: 3',
                    'class' => 'w-full px-3 py-2 bg-white/20 border border-white/30 rounded-lg text-backgroundDark placeholder-backgroundDark/60 focus:outline-none focus:border-accent backdrop-blur-sm'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nombre de places disponibles est obligatoire']),
                    new Assert\Range(['min' => 1, 'max' => 7, 'notInRangeMessage' => 'Le nombre de places doit être entre {{ min }} et {{ max }}'])
                ]
            ])
            ->add('vehicle', EntityType::class, [
                'class' => Vehicle::class,
                'choices' => $user->getVehicles(),
                'choice_label' => function(Vehicle $vehicle) {
                    return $vehicle->getBrand() . ' ' . $vehicle->getModel() . ' (' . $vehicle->getPlate() . ')';
                },
                'label' => 'Véhicule',
                'attr' => [
                    'class' => 'w-full px-3 py-2 bg-white/20 border border-white/30 rounded-lg text-backgroundDark focus:outline-none focus:border-accent backdrop-blur-sm'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Vous devez sélectionner un véhicule'])
                ],
                'placeholder' => 'Sélectionnez un véhicule'
            ])

            ->add('submit', SubmitType::class, [
                'label' => 'Proposer le voyage',
                'attr' => [
                    'class' => 'w-full bg-accent hover:bg-accentDark text-textSecondary hover:text-textPrimary font-base py-3 px-6 rounded-lg transition-all duration-300 shadow-lg hover:shadow-xl'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ride::class,
            'user' => null,
        ]);
    }
} 