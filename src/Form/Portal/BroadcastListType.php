<?php

namespace App\Form\Portal;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class BroadcastListType extends AbstractType
{
    protected $csrf;
    protected $security;

    public function __construct(CsrfTokenManagerInterface $csrf, Security $security)
    {
        $this->csrf = $csrf;
        $this->security = $security;
    }

    /**
     * Builds the Employee Registration form.
     *
     * @param \Symfony\Component\Form\FormBuilder $builder
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $ou = $options['ou'];
        
        $em = $options['em'];
        $factory = $builder->getFormFactory();

        
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            $builder->add('organizationUnit', EntityType::class, [
                'class' => 'App:Portal\OrganizationUnit',
                'choice_label' => 'OUName',
                'placeholder' => 'Select Organization Unit',
                'attr' => ['class' => 'searchable oubox'],
                'choice_attr' => function ($organizationUnit) {
                    return ['data-prefix' => strtolower($organizationUnit->getOUCode())];
                },
            ]);
        }
        $builder->add('guId', HiddenType::class, ['attr' => ['class' => 'formGuId']]);
        $builder->add('listName', TextType::class);
        $builder->add('listCategory', EntityType::class, [
            'class' => 'App\Entity\Masters\ListCategory',
            'placeholder' => 'Select category ',
            'choice_label' => 'categoryName',
            'attr' => ['class' => 'searchable'],
        ]);
        $builder->add('visibility', EntityType::class, [
            'class' => 'App\Entity\Masters\ListVisibility',
            'placeholder' => 'Select Visibility ',
            'choice_label' => 'visibilityType',
            'attr' => ['class' => 'searchable'],
        ]);
        $builder->add('membershipType', EntityType::class, [
            'class' => 'App\Entity\Masters\MembershipType',
            'placeholder' => 'Select Membership Type ',
            'choice_label' => 'membershipType',
            'attr' => ['class' => 'searchable'],
        ]);
        $builder->add('priority', ChoiceType::class, [
            'choices' => [
                'Low' => 'L',
                'High' => 'H', ],
            'attr' => ['class' => 'searchable'], ]);
        $builder->add('AllowUnSubscribe', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class,['required'=>false]);
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $formName = $form->getName();
            $this->csrf->getToken($formName);
            $this->csrf->removeToken($formName);
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => true, 'data_class' => 'App\Entity\Lists\BroadcastList',
            'ou' => null, 'tokenStorage' => null, 'em' => null,
        ]);
    }

    /**
     * Gets the unique name of this form.
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'BroadcastList';
    }
}
