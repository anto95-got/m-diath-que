<?php

namespace App\Form;

use App\Entity\Emprunt;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmpruntType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('idUtilisateur', EntityType::class, [
                'class' => Utilisateur::class,
                'choice_label' => function (Utilisateur $u) {
                    return sprintf('%s %s (%s)', $u->getNom(), $u->getPrenom(), $u->getEmail());
                },
                'label' => 'Utilisateur',
                'placeholder' => 'Choisir un utilisateur',
            ])
            ->add('codeBarres', TextType::class, [
                'mapped' => false,
                'label' => 'Code-barres du document',
                'attr' => ['autocomplete' => 'off'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Emprunt::class,
        ]);
    }
}
