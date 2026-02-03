<?php

namespace App\Form;

use App\Entity\Campus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortieFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'nom',
                'label' => 'Campus',
                'required' => false,
            ])
            ->add('rechercheNom', TextType::class, [
                'label' => 'Le nom de la sortie contient :',
                'required' => false,
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Entre le',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'et le',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('estOrganisateur', CheckboxType::class, [
                'label' => "Sorties dont je suis l'organisateur/trice",
                'required' => false,
            ])
            ->add('estInscrit', CheckboxType::class, [
                'label' => "Sorties auxquelles je suis inscrit/e",
                'required' => false,
            ])
            ->add('nEstPasInscrit', CheckboxType::class, [
                'label' => "Sorties auxquelles je ne suis pas inscrit/e",
                'required' => false,
            ])
            ->add('sortiesTerminees', CheckboxType::class, [
                'label' => "Sorties passées",
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // On utilise GET pour que l'utilisateur puisse partager son URL de recherche
            'method' => 'GET',
            'csrf_protection' => false, // Optionnel pour un formulaire de recherche
        ]);
    }
}
