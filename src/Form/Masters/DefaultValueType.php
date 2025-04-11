<?php

namespace App\Form\Masters;

use App\Entity\Masters\DefaultValue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class DefaultValueType extends AbstractType {

    protected $csrf;
    public function __construct(CsrfTokenManagerInterface $csrf) {
        $this->csrf = $csrf;
     }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('code');
        $builder->add('environment');
        $builder->add('description');
        $builder->add('defaultValue');
        // $builder->add('id', HiddenType::class);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $formName = $form->getName();
            $this->csrf->getToken($formName);
            $this->csrf->removeToken($formName);
        });
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'data_class' => DefaultValue::class
        ]);
    }

}
