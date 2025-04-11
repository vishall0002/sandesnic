<?php

namespace App\Form\Portal;

use App\Entity\Portal\OrganizationUnit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;



class OrganizationUnitType extends AbstractType
{
    public function __construct(RouterInterface $router, CsrfTokenManagerInterface $csrf, Security $security, AuthorizationCheckerInterface $auth)
    {
        $this->router = $router;
        $this->csrf = $csrf;
        $this->auth = $auth;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('OUCode');
        $builder->add('OUName');
        $builder->add('organizationUnitType', EntityType::class, array(
            'class' => 'App\Entity\Masters\OrganizationType',
            'placeholder' => 'Select Organization Type',
            'attr' => array('class' => 'searchable'),
        ));

        $builder->add('address');
        $builder->add('state', EntityType::class, array(
                    'label' => 'State',
                    'class' => 'App:Masters\State',
                    'placeholder' => '----Select State----',
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('s')
                                ->orderBy('s.sortOrder', 'DESC')
                                ->where('s.isPublished=true');
                    },
                    'choice_label' => 'state',
                    'attr' => array('class' => 'searchable ou_state', 'data-path' => $this->router->generate('portal_ou_district_loader')),
                    'required' => true,
                ));
        $builder->add('pinCode');
        $builder->add('landline');
        $builder->add('website');
        $builder->add('guId', HiddenType::class);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();
            $state = (null === $data ? array() : $data->getState());
            $profile = $form->getConfig()->getOptions()['profile'];
            // We are injecting the direct profile object so that 3 variables are received at one go
            if ($profile) {
                if ($this->auth->isGranted('ROLE_MINISTRY_ADMIN')) {
                    $organization = (null === $data ? array() : $data->getOrganization());
                    $ministry = $profile->getMinistry();
                } elseif ($this->auth->isGranted('ROLE_O_ADMIN')) {
                    $organization = $profile->getOrganization();
                    $ministry = $profile->getMinistry();
                } elseif ($this->auth->isGranted('ROLE_OU_ADMIN')) {
                    $organization = $profile->getOrganization();
                    $ministry = $profile->getMinistry();
                } elseif ($this->auth->isGranted('ROLE_SUPER_ADMIN')) {
                    $organization = (null === $data ? array() : $data->getOrganization());
                    $ministry = (null === $data->getOrganization() ? array() : $data->getOrganization()->getMinistry());
                }
            } else {
                $organization = (null === $data ? array() : $data->getOrganization());
                $ministry = (null === $data->getOrganization() ? array() : $data->getOrganization()->getMinistry());
            }

            $this->loadDistrict($form, $state);
            $this->loadOrganization($form, $ministry);
            $this->loadParentOU($form, $organization);
        });
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();
            $state = (null === $data ? array() : $data['state']);
            $profile = $form->getConfig()->getOptions()['profile'];
            // We are injecting the direct profile object so that 3 variables are received at one go
            if ($profile) {
                if ($this->auth->isGranted('ROLE_MINISTRY_ADMIN')) {
                    $organization = (null === $data ? array() : $data['organization']);
                    $ministry = $profile->getMinistry();
                } elseif ($this->auth->isGranted('ROLE_O_ADMIN')) {
                    $organization = $profile->getOrganization();
                    $ministry = $profile->getMinistry();
                } elseif ($this->auth->isGranted('ROLE_OU_ADMIN')) {
                    $organization = $profile->getOrganization();
                    $ministry = $profile->getMinistry();
                } elseif ($this->auth->isGranted('ROLE_SUPER_ADMIN')) {
                    $organization = (null === $data ? array() : $data['organization']);
                    $ministry = (null === $data ? array() : $data['ministry']);
                }
            } else {
                $organization = (null === $data ? array() : $data['organization']);
                $ministry = (null === $data ? array() : $data['ministry']);
            }
            $this->loadDistrict($form, $state);
            $this->loadOrganization($form, $ministry);
            $this->loadParentOU($form, $organization);
        });
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $formName = $form->getName();
            $this->csrf->getToken($formName);
            $this->csrf->removeToken($formName);
        });
    }

    private function loadDistrict($form, $state)
    {
        $form->add('district', EntityType::class, array(
            'class' => 'App:Masters\District',
            'choice_label' => 'district',
            'placeholder' => '---Select District---',
            'required' => true,
            'query_builder' => function (EntityRepository $repository) use ($state) {
                return $qb = $repository->createQueryBuilder('d')
                        ->where('d.state= :state')
                        ->andWhere('d.isPublished=true')
                        ->setParameter('state', $state);
            },
            'attr' => array('class' => 'searchable ou_district'),
        ));
    }

    private function loadParentOU($form, $organization)
    {
        $form->add('parentOrganizationUnit', EntityType::class, array(
            'class' => 'App\Entity\Portal\OrganizationUnit',
            'placeholder' => 'Select Parent Organization Unit',
            'required' => false,
            'query_builder' => function (EntityRepository $repository) use ($organization) {
                return $qb = $repository->createQueryBuilder('ou')
                        ->where('ou.organization= :organization')
                        ->setParameter('organization', $organization);
            },
            'attr' => array('class' => 'searchable sbox-parentou'),
        ));
    }

    private function loadOrganization($form, $ministry)
    {
        $form->add('ministry', EntityType::class, array(
            'class' => 'App\Entity\Masters\Ministry',
            'placeholder' => 'Select Ministry',
            'mapped' => false,
            'data' => $ministry,
            'attr' => array('class' => 'searchable sbox-ministry', 'data-path' => $this->router->generate('portal_o_get_os_by_ministry')),
        ));

        $form->add('organization', EntityType::class, array(
            'class' => 'App\Entity\Portal\Organization',
            'placeholder' => 'Select Organization',
            'query_builder' => function (EntityRepository $repository) use ($ministry) {
                return $qb = $repository->createQueryBuilder('o')
                        ->where('o.ministry= :ministry')
                        ->setParameter('ministry', $ministry);
            },
            'attr' => array('class' => 'searchable sbox-o', 'data-path' => $this->router->generate('portal_ou_get_ous_by_o')),
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => true, 'data_class' => OrganizationUnit::class, 'profile' => null,
        ]);
    }
}
