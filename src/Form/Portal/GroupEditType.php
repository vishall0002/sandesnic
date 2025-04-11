<?php

namespace App\Form\Portal;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityRepository;

class GroupEditType extends AbstractType
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
     * Builds the Employee Registration form.
     *
     * @param \Symfony\Component\Form\FormBuilder $builder
     * @param array                               $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $ou = $options['ou'];
        
        $em = $options['em'];
        $factory = $builder->getFormFactory();

        
        // if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
        //     $builder->add('organizationUnit', EntityType::class, array(
        //         'class' => 'App:Portal\OrganizationUnit',
        //         'choice_label' => 'OUName',
        //         'placeholder' => 'Select Organization Unit',
        //        'attr' => array('class' => 'searchable oubox'),
        //        'choice_attr' => function ($organizationUnit) {
        //            return ['data-prefix' => strtolower($organizationUnit->getOUCode())];
        //        },
        //     ));
        // }
        $builder->add('guId', HiddenType::class);
        // $builder->add('groupType', EntityType::class, array(
        //     'class' => 'App:Masters\GroupType',
        //     'choice_label' => 'groupTypeName',
        //     'placeholder' => 'Select Group Type',
        //     'attr' => array('class' => 'searchable, gtbox'),
        //     'choice_attr' => function ($groupType) {
        //         return ['data-suffix' => strtolower($groupType->getGroupSuffix())];
        //     },
        // ));
        // $builder->add('groupPurpose', EntityType::class, array(
        //     'class' => 'App:Masters\GroupPurpose',
        //     'choice_label' => 'groupPurposeName',
        //     'placeholder' => 'Select Group Purpose',
        //     'attr' => array('class' => 'searchable'),
        // ));
        // $builder->add('groupCreation', EntityType::class, array(
        //     'class' => 'App:Masters\GroupCreation',
        //     'choice_label' => 'groupCreationName',
        //     'placeholder' => 'Select Group Creation',
        //     'attr' => array('class' => 'searchable'),
        // ));
        $builder->add('groupTitle', TextType::class);
        $builder->add('groupDescription', TextType::class);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();
            $profile = $form->getConfig()->getOptions()['profile'];
            $role = 'ROLE_SUPER_ADMIN';
            // We are injecting the direct profile object so that 3 variables are received at one go
            if ($profile) {
                if ($this->security->isGranted('ROLE_MINISTRY_ADMIN')) {
                    $role = 'ROLE_MINISTRY_ADMIN';
                    $ministry = $profile->getMinistry();
                    $ou = (null === $data ? [] : $data->getOrganizationUnit());
                    $organization = (null === $data->getOrganizationUnit() ? [] : $data->getOrganizationUnit()->getOrganization());
                } elseif ($this->security->isGranted('ROLE_O_ADMIN')) {
                    $role = 'ROLE_O_ADMIN';
                    $ou = (null === $data ? [] : $data->getOrganizationUnit());
                    $organization = $profile->getOrganization();
                    $ministry = $profile->getMinistry();
                } elseif ($this->security->isGranted('ROLE_OU_ADMIN')) {
                    $role = 'ROLE_OU_ADMIN';
                    $organization = $profile->getOrganization();
                    $ministry = $profile->getMinistry();
                    $ou = $profile->getOrganizationUnit();
                } elseif ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
                    $ou = (null === $data ? [] : $data->getOrganizationUnit());
                    $organization = (null === $data->getOrganizationUnit() ? [] : $data->getOrganizationUnit()->getOrganization());
                    $ministry = (null === $data->getOrganizationUnit() ? [] : $data->getOrganizationUnit()->getOrganization()->getMinistry());
                }
            } else {
                $ou = (null === $data ? [] : $data->getOrganizationUnit());
                $organization = (null === $data->getOrganizationUnit() ? [] : $data->getOrganizationUnit()->getOrganization());
                $ministry = (null === $data->getOrganizationUnit() ? [] : $data->getOrganizationUnit()->getOrganization()->getMinistry());
            }
            
            $this->loadRoleWiseControls($form, $ministry, $organization);
        });
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();
            $profile = $form->getConfig()->getOptions()['profile'];
            $role = 'ROLE_SUPER_ADMIN';
            // We are injecting the direct profile object so that 3 variables are received at one go
            if ($profile) {
                if ($this->security->isGranted('ROLE_MINISTRY_ADMIN')) {
                    $role = 'ROLE_MINISTRY_ADMIN';
                    $ministry = $profile->getMinistry();
                    $ou = (null === $data ? [] : $data['organizationUnit']);
                    $organization = (null === $data ? [] : $data['organization']);
                } elseif ($this->security->isGranted('ROLE_O_ADMIN')) {
                    $role = 'ROLE_O_ADMIN';
                    $ou = (null === $data ? [] : $data['organizationUnit']);
                    $organization = $profile->getOrganization();
                    $ministry = $profile->getMinistry();
                } elseif ($this->security->isGranted('ROLE_OU_ADMIN')) {
                    $role = 'ROLE_OU_ADMIN';
                    $organization = $profile->getOrganization();
                    $ministry = $profile->getMinistry();
                    $ou = $profile->getOrganizationUnit();
                } elseif ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
                    $role = 'ROLE_SUPER_ADMIN';
                    $ou = (null === $data ? [] : $data['organizationUnit']);
                    $organization = (null === $data ? [] : $data['organization']);
                    $ministry = (null === $data ? [] : $data['ministry']);
                }
            } else {
                $ou = (null === $data ? [] : $data['organizationUnit']);
                $organization = (null === $data ? [] : $data['organization']);
                $ministry = (null === $data ? [] : $data['ministry']);
            }
           
            $this->loadRoleWiseControls($form, $ministry, $organization);
        });

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
            'data_class' => 'App\Entity\Portal\Group',
            'csrf_protection' => true,
            'ou' => null, 'tokenStorage' => null, 'em' => null,'profile' => null,
        ));
    }

    /**
     * Gets the unique name of this form.
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'Group';
    }


    private function loadRoleWiseControls($form, $ministry, $organization )
    {
        $form->add('ministry', EntityType::class, [
            'class' => 'App\Entity\Masters\Ministry',
            'placeholder' => 'Select Ministry',
            'mapped' => false,
            'data' => $ministry,
            'attr' => ['class' => 'searchable sbox-ministry', 'data-path' => $this->router->generate('portal_o_get_os_by_ministry')],
        ]);

        $form->add('organization', EntityType::class, [
            'class' => 'App\Entity\Portal\Organization',
            'placeholder' => 'Select Organization',
            'mapped' => false,
            'data' => $organization,
            'query_builder' => function (EntityRepository $repository) use ($ministry) {
                return $qb = $repository->createQueryBuilder('o')
                        ->where('o.ministry= :ministry')
                        ->setParameter('ministry', $ministry);
            },
            'attr' => ['class' => 'searchable sbox-o', 'data-path' => $this->router->generate('portal_get_ou_dg_el_by_organization')],
        ]);

        $form->add('organizationUnit', EntityType::class, [
            'class' => 'App\Entity\Portal\organizationUnit',
            'placeholder' => 'Select  Organization Unit',
            'query_builder' => function (EntityRepository $repository) use ($organization) {
                return $qb = $repository->createQueryBuilder('ou')
                        ->where('ou.organization= :organization')
                        ->setParameter('organization', $organization);
            },
            'attr' => ['class' => 'searchable sbox-parentou'],
        ]);

    

 
    }


}
