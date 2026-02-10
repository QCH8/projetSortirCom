<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Participant;
use App\Entity\Sortie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
class ParticipantType extends AbstractType
{
    // src/Form/ParticipantType.php

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Style Tailwind réutilisable pour tous les champs
        $inputClass = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm';

        $builder
            ->add('pseudo', null, ['attr' => ['class' => $inputClass]])
            ->add('prenom', null, ['attr' => ['class' => $inputClass]])
            ->add('nom', null, ['attr' => ['class' => $inputClass]])
            ->add('telephone', TelType::class, ['attr' => ['class' => $inputClass]])
            ->add('mail', EmailType::class, ['attr' => ['class' => $inputClass]])
            /* GESTION DU MOT DE PASSE :
               - RepeatedType : crée automatiquement deux champs (mot de passe + confirmation)
               - mapped => false : évite que Symfony n'écrase le mot de passe actuel par erreur
               - required => false : permet de modifier son profil sans changer son mot de passe
            */
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false, // Important : ne pas lier directement à l'entité
                'required' => false,
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                // L'option 'options' s'applique aux deux champs (Mot de passe et Confirmation)
                'options' => ['attr' => ['class' => $inputClass]],
                'first_options'  => ['label' => 'Mot de passe'],
                'second_options' => ['label' => 'Confirmer le mot de passe'],
            ])
            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'nom',
                'attr' => ['class' => $inputClass],
                'disabled' => !$options['is_admin'], 
                'help' => !$options['is_admin'] ? 'Seul un administrateur peut modifier votre campus.' : null,
            ])

            ->add('photo', FileType::class, [
                'label' => 'Photo de profil (JPG, PNG)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '2M',
                        mimeTypes: ['image/jpeg', 'image/png'],
                        mimeTypesMessage: 'Veuillez envoyer une image JPG ou PNG valide.',
                    )
                ],
            ]);

            $builder->add('delete_photo', CheckboxType::class, [
                'label' => 'Supprimer la photo actuelle',
                'mapped' => false,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
            'is_admin' => false,
        ]);
        $resolver->setAllowedTypes('is_admin', 'bool');
    }
}
