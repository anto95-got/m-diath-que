<?php

namespace App\Form;

use App\Entity\Auteur;
use App\Entity\Categorie;
use App\Entity\Document;
use App\Entity\Etat;
use App\Entity\SousCategorie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
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
            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'nomCategorie',
                'label' => 'Catégorie',
                'placeholder' => 'Choisir une catégorie',
                'mapped' => false,
                'required' => true,
                'data' => $options['selected_categorie'],
            ])
            ->add('idSousCategorie', EntityType::class, [
                'class' => SousCategorie::class,
                'choice_label' => 'nomSousCategorie',
                'label' => 'Sous-catégorie',
                'placeholder' => 'Choisir une sous-catégorie',
                'choice_attr' => static function (?SousCategorie $sousCategorie): array {
                    $categorieId = $sousCategorie?->getIdCategorie()?->getIdCategorie();

                    return [
                        'data-categorie-id' => $categorieId ? (string) $categorieId : '',
                    ];
                },
            ])
            ->add('auteurs', EntityType::class, [
                'class' => Auteur::class,
                'choice_label' => 'nomPrenom',
                'label' => 'Auteur(s)',
                'multiple' => true,
                'required' => false,
                'attr' => ['data-select' => 'true'],
            ])
            ->add('newAuteur', TextType::class, [
                'label' => 'Ajouter un nouvel auteur',
                'mapped' => false,
                'required' => false,
            ])
            ->add('nombreExemplaires', IntegerType::class, [
                'label' => 'Nombre d\'exemplaires à ajouter',
                'mapped' => false,
                'required' => true,
                'attr' => ['min' => 1, 'max' => 100],
                'data' => $options['initial_copies'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Document::class,
            'selected_categorie' => null,
            'initial_copies' => 1,
        ]);

        $resolver->setAllowedTypes('selected_categorie', ['null', Categorie::class]);
        $resolver->setAllowedTypes('initial_copies', ['int']);
    }
}
