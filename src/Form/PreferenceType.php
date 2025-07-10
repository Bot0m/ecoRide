<?php

namespace App\Form;

use App\Entity\Preference;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PreferenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('smoker', CheckboxType::class, [
                'label' => 'J\'autorise les fumeurs',
                'required' => false,
                'attr' => [
                    'class' => 'w-4 h-4 text-accent bg-white/20 border-white/30 rounded focus:ring-2 focus:ring-accent'
                ]
            ])
            ->add('animals', CheckboxType::class, [
                'label' => 'J\'autorise les animaux',
                'required' => false,
                'attr' => [
                    'class' => 'w-4 h-4 text-accent bg-white/20 border-white/30 rounded focus:ring-2 focus:ring-accent'
                ]
            ])
            ->add('customPreferences', TextareaType::class, [
                'label' => 'Autres préférences',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Décrivez vos autres préférences de voyage...',
                    'class' => 'w-full px-3 py-2 bg-white/20 border border-white/30 rounded-lg text-backgroundDark placeholder-backgroundDark/60 focus:outline-none focus:border-accent backdrop-blur-sm resize-none',
                    'rows' => 3
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Preference::class,
        ]);
    }
} 