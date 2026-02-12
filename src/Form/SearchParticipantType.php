<?php

namespace App\Form;

use App\Entity\Campus;
use App\Model\SearchParticipant;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchParticipantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'nom',
                'placeholder' => 'Tous les campus',
                "required" => false,
            ])
            ->add('nom', SearchType::class,[
                'label' => 'Recherche par nom',
                'required' => false,
                'attr' => ['placeholder' => 'Rechercher...'],
            ])
            ->add('actifSeulement', CheckboxType::class, [
                'label' => 'Actifs Seulement',
                'required' => false,
            ])
            ->add('submit', SubmitType::class,[
                'label' => 'Rechercher',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchParticipant::class,
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}
