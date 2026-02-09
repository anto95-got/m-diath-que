<?php

namespace App\Form;

use App\Entity\DemandeDocument;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
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
            ->add('quantiteDemandee', IntegerType::class, [
                'label' => 'Nombre d\'exemplaires souhaités',
                'attr' => ['min' => 1, 'max' => 20],
                'data' => 1,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DemandeDocument::class,
        ]);
    }
}
