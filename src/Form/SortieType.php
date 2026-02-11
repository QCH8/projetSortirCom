<?php

namespace App\Form;

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
            // Nom de l'événement
            ->add('nom', TextType::class, [
                'label' => 'Nom de la sortie'
            ])
            // Date et heure du début (widget single_text pour l'agenda HTML5)
            ->add('dateHeureDebut', DateTimeType::class, [
                'label' => 'Date et heure de la sortie',
                'widget' => 'single_text',
                'required' => true,
                'attr' => [
                    'min' => $now->format('Y-m-d\TH:i')
                ]
            ])
            // Date limite pour s'inscrire
            ->add('dateLimiteInscription', DateTimeType::class, [
                'label' => 'Date limite d\'inscription',
                'widget' => 'single_text',
                'required' => true,
                'attr' => [
                    'min' => $now->format('Y-m-d\TH:i')
                ]
            ])
            // Nombre de places disponibles
            ->add('nbInscriptionsMax', IntegerType::class, [
                'label' => 'Nombre de places',
                'attr' => ['min' => 1]
            ])
            // Durée de l'activité
            ->add('duree', IntegerType::class, [
                'label' => 'Durée (en minutes)',
                'attr' => ['min' => 1]
            ])
            // Description libre
            ->add('infosSortie', TextareaType::class, [
                'label' => 'Description et infos',
                'required' => false,
            ])
            // Ville : Non "mappé" car la sortie est liée au Lieu, pas directement à la Ville
            ->add('ville', EntityType::class, [
                'class' => Ville::class,
                'choice_label' => 'nom',
                'placeholder' => '-- Choisir une ville --',
                'mapped' => false,
                'label' => 'Ville'
            ])

            // Liste déroulante des Lieux
            ->add('lieu', EntityType::class, [
                'class' => Lieu::class,
                'choice_label' => 'nom',
                'placeholder' => '-- Choisir un lieu --',
                'label' => 'Lieu'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
        ]);
    }
}
