<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Lieu;
use App\Entity\Sortie;
use App\Entity\Ville;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $now = new \DateTimeImmutable();

        $builder
            // --- CHAMPS DE L'ENTITÉ ---
            ->add('nom', TextType::class, [
                'label' => 'Nom de la sortie'
            ])
            ->add('dateHeureDebut', DateTimeType::class, [
                'label' => 'Date et heure de la sortie',
                'widget' => 'single_text',
                'required' => true,
                'attr' => [
                    'min' => $now->format('Y-m-d\TH:i')
                ]
            ])
            ->add('dateLimiteInscription', DateTimeType::class, [
                'label' => 'Date limite d\'inscription',
                'widget' => 'single_text',
                'required' => true,
                'attr' => [
                    'min' => $now->format('Y-m-d\TH:i')
                ]
            ])
            ->add('nbInscriptionsMax', IntegerType::class, [
                'label' => 'Nombre de places',
                'attr' => ['min' => 1]
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Durée (en minutes)',
                'attr' => ['min' => 1]
            ])
            ->add('infosSortie', TextareaType::class, [
                'label' => 'Description et infos',
                'required' => false,
            ])
            ->add('lieu', EntityType::class, [
                'class' => Lieu::class,
                'choice_label' => 'nom',
                'placeholder' => '-- Choisir un lieu --',
                'label' => 'Lieu'
            ])

        // --- CHAMPS NON MAPPÉS (Virtuels) ---
        // Le champ Ville ne fait pas partie de l'entité Sortie. Je l'ajoute ici pour permettre le filtrage dynamique des lieux.
        // J'utilise l'option 'ville_auto' (passée depuis le contrôleur) pour pré-remplir la valeur en cas de modification.
        ->add('ville', EntityType::class, [
        'class' => Ville::class,
        'choice_label' => 'nom',
        'placeholder' => '-- Choisir une ville --',
        'mapped' => false,
        'label' => 'Ville',
        'required' => false,
        'data' => $options['ville_auto']
    ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
            // Je définis une option par défaut à null pour éviter les erreurs lors de la création (où il n'y a pas de ville)
            'ville_auto' => null,
        ]);
    }
}
