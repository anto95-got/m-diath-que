<?php

namespace App\Form;

use App\Entity\Auteur;
use App\Entity\Document;
use App\Entity\Etat;
use App\Entity\SousCategorie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
            ])
            ->add('codeBarres', TextType::class, [
                'label' => 'Code-barres',
            ])
            ->add('disponible', CheckboxType::class, [
                'label' => 'Disponible',
                'required' => false,
            ])
            ->add('idEtat', EntityType::class, [
                'class' => Etat::class,
                'choice_label' => 'libelleEtat',
                'label' => 'État',
                'placeholder' => 'Choisir un état',
            ])
            ->add('idSousCategorie', EntityType::class, [
                'class' => SousCategorie::class,
                'choice_label' => 'nomSousCategorie',
                'label' => 'Sous-catégorie',
                'placeholder' => 'Choisir une sous-catégorie',
            ])
            ->add('auteurs', EntityType::class, [
                'class' => Auteur::class,
                'choice_label' => 'nomPrenom',
                'label' => 'Auteur(s)',
                'multiple' => true,
                'required' => false,
                'attr' => ['data-select' => 'true'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Document::class,
        ]);
    }
}
