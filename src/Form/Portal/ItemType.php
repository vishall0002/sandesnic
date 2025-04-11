<?php

namespace App\Form\Portal;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ItemType extends AbstractType
{
    protected $csrf;
    protected $security;
    protected $router;

    public function __construct(RouterInterface $router, CsrfTokenManagerInterface $csrf, Security $security)
    {
        $this->csrf = $csrf;
        $this->security = $security;
        $this->router = $router;
    }
    /**
     * Builds the Portal Item form.
     *
     * @param \Symfony\Component\Form\FormBuilder $builder
     * @param array                               $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('guId', HiddenType::class);

        $builder->add('itemName', ChoiceType::class, array(
            'choices' => array(
                'Android' => 'Android',
                'iOS' => 'iOS'
            ),
            'placeholder' => 'Select Item Name',
            'required' => true,
            'attr'=>[
                'class'=>'searchable'
            ],
        ));

        $builder->add('itemType', ChoiceType::class, array(
            'choices' => array(
                'About' => 'About',
                'Support' => 'Support',
                'Privacy' => 'Privacy'
            ),
            'placeholder' => 'Select Item Type',
            'required' => true,
            'attr'=>[
                'class'=>'searchable'
            ],
        ));

        $builder->add('itemText', TextareaType::class, 
        [
            'required' => true,
            'constraints' => [
                new NotBlank()
            ],
            'attr' => array(
                'class' => 'preview_textarea'
            )
        ]);


        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $formName = $form->getName();
            $this->csrf->getToken($formName);
            $this->csrf->removeToken($formName);
        });
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => true, 
            'data_class' => 'App\Entity\Portal\Item',
            'tokenStorage' => null,
        ));
    }
}
