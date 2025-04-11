<?php

namespace App\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

class ChangePasswordType extends AbstractType
{
    private $currentUserSalt;

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->currentUserSalt = $options["currentUserSalt"];
        $builder->add('current_password', PasswordType::class, array('validation_groups' => array('Default'),   'constraints' => new UserPassword(array('message' => 'Current Password entered is wrong !'))));
        $builder->add('new_password', 'repeated', array(
            'mapped'          => false,
            'type'            => 'password',
            'invalid_message' => 'The password fields must match.',
            'required'        => true,
            'first_options'   => array('label' => 'Password'),
            'second_options'  => array('label' => 'Repeat Password'),
        ));
        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmission'));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'currentUserSalt' => null,
        ));
    }
    public function onPreSubmission(FormEvent $event)
    {
        $data = $event->getData();
        if (!$data) {
            return;
        }
        if (null !== $data['current_password']) {
            $data['current_password'] = bin2hex(hash('sha256', $data['current_password'].'{'.$this->currentUserSalt.'}', true));
            $event->setData($data);
        }
    }
    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'selfChangePassword';
    }
}
