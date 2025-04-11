<?php

namespace App\Form\Portal;

use App\Entity\Portal\Organization;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityRepository;


class OrganizationType extends AbstractType
{
    protected $csrf;
    protected $security;

    public function __construct(CsrfTokenManagerInterface $csrf,  Security $security)
    {
        $this->csrf = $csrf;
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('organizationCode');
        $builder->add('organizationName');
        $builder->add('ministry', EntityType::class, array(
            'class' => 'App\Entity\Masters\Ministry',
            'placeholder' => 'Select Ministry',
            'attr' => array('class' => 'searchable'),
        ));
        $builder->add('organizationType', EntityType::class, array(
            'class' => 'App\Entity\Masters\OrganizationType',
            'placeholder' => 'Select Organization Type',
            'attr' => array('class' => 'searchable'),
        ));
        if (!($this->security->isGranted('ROLE_O_ADMIN'))) {
            $builder->add('vhostId', EntityType::class, array(
                'class' => 'App\Entity\Masters\Vhost',
                'placeholder' => 'Select Vhost',
                'choice_label' => 'vhostAlias',
                'label' => 'Vhost',
                'mapped' => true,
                'attr' => array('class' => 'searchable'),
                'query_builder' => function (EntityRepository $repository) {
                    return $repository->createQueryBuilder('s')
                    ->orderBy('s.sortOrder', 'DESC')
                    ->where('s.isPublished=true');
                },
            ));
        }

        $builder->add('isOVisibility', ChoiceType::class, [ 'choices' => [
            'Select' => '',
            'Visible' => true, 
            'Invisible' => false,
        ],
        'label' => 'Organization Visibility' ,
        'attr' => array('class' => 'searchable')]
        );

        $builder->add('isPublicVisibility', ChoiceType::class, [ 'choices' => [
            'Select' => '',
            'Visible' => true, 
            'Invisible' => false,
        ],
        'label' => 'Public Visibility',
        'attr' => array('class' => 'searchable')]
    );

        $builder->add('guId', HiddenType::class);
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
            'csrf_protection' => true, /*'csrf_token_id' => 'form_intention',*/ 'data_class' => Organization::class,
        ]);
    }
}
