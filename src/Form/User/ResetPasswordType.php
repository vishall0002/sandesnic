<?php

namespace App\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

class ResetPasswordType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
          ->add('newPassword', PasswordType::class, array(
            'invalid_message' => 'Passwords must match.',
            'required' => true,
          ))
          ->add('confirmPassword', PasswordType::class, array(
            'invalid_message' => 'Passwords must match.',
            'required' => true,
          ))
          ->add('update', SubmitType::class, array(
              'attr' => array('class' => 'btn btn-primary btn-xs update'),
          ))
          ->add('reset', ResetType::class, array(
              'attr' => array('class' => 'btn btn-default btn-xs'),
          ));
    }
}
