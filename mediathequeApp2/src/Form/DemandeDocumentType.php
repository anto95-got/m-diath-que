<?php

namespace App\Form;

use App\Entity\DemandeDocument;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DemandeDocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titreDemande', TextType::class, [
                'label' => 'Titre du livre',
            ])
            ->add('auteurDemande', TextType::class, [
                'label' => 'Auteur',
            ])
            ->add('typeDemande', ChoiceType::class, [
                'label' => 'Type de demande',
                'choices' => [
                    'Réservation' => 'reservation',
                    'Proposition d\'achat' => 'proposition',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DemandeDocument::class,
        ]);
    }
}
