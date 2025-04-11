<?php

namespace App\Form\Portal;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class EmployeeListType extends AbstractType {

    /**
     * Builds the Employee Registration form
     *
     * @param  \Symfony\Component\Form\FormBuilder $builder
     * @param  array                               $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder->add('organizationUnit', EntityType::class, array(
            'class' => 'App:Portal\OrganizationUnit',
            'choice_label' => 'organizationName',
            'choice_value' => 'guId',
            'placeholder' => 'Select Organization Unit'
        ));
//        $builder->add('status', ChoiceType::class, array(
//            'choices' => array(
//                'Active' => '1',
//                'Inactive' => '0'
//            ) ,
//            'required' => false,
//            'placeholder' => 'All',
//        ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
    }

    /**
     * Mandatory in Symfony2
     * Gets the unique name of this form.
     *
     * @return string
     */
    public function getBlockPrefix() {
        return 'EmployeeList';
    }

}
