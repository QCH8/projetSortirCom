<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Participant;
use App\Entity\Sortie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminModifyParticipantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $input = 'w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500';

        $builder
            ->add('nom', TextType::class, [
                'label' =>'Nom',
                'attr' => ['class' => $input]
            ])
            ->add('prenom', TextType::class,[
                'label' =>'Prenom',
                'attr' => ['class' => $input]
            ])
            ->add('pseudo', TextType::class,[
                'label' =>'Pseudo',
                'attr' => ['class' => $input]
            ])
            ->add('telephone', TelType::class,[
                'label' =>'Téléphone',
                'required' =>false,
                'attr' => ['class' => $input]
            ])
            ->add('mail', EmailType::class,[
                'label' =>'Email',
                'attr' => ['class' => $input]
            ])
            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'nom',
                'placeholder' => '-- Choisir un campus --',
                'label' => 'Campus',
                'attr' => ['class' => $input],
            ])
            ->add('administrateur', CheckboxType::class, [
                'label' => 'Administrateur',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
        ]);
    }
}
