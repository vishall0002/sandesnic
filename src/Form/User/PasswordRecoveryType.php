<?php

namespace App\Form\User;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class PasswordRecoveryType extends AbstractType {


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('userIdForSMS', TextType::class, array(
                'required'    => true,
                 'attr' => array('autocomplete' => 'off')
            ))
            ->add('mobile', TextType::class, array(
                'required'    => true,
                 'attr' => array('autocomplete' => 'off')
            ));
    }
}
