<?php

namespace App\Form;

use App\Entity\Role;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UtilisateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
            ]);

        if ($options['can_edit_password']) {
            $builder->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'required' => false,
                'help' => $options['password_help'],
            ]);
        }

        if ($options['can_edit_role']) {
            $builder->add('idRole', EntityType::class, [
                'class' => Role::class,
                'choice_label' => 'nomRole',
                'label' => 'Rôle',
                'placeholder' => 'Sélectionner un rôle',
                'required' => true,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
            'can_edit_role' => true,
            'can_edit_password' => true,
            'password_help' => null,
        ]);

        $resolver->setAllowedTypes('can_edit_role', 'bool');
        $resolver->setAllowedTypes('can_edit_password', 'bool');
        $resolver->setAllowedTypes('password_help', ['null', 'string']);
    }
}
