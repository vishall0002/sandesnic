<?php

namespace App\Form\Portal;

use App\Entity\Portal\Employee;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class EmployeeType extends AbstractType
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
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $factory = $builder->getFormFactory();
        $regMode = $options['regMode'];
        $builder->add('guId', HiddenType::class);
        $builder->add('employeeCode', TextType::class);
        $builder->add('gender', EntityType::class, [
            'class' => 'App:Masters\Gender',
            'choice_label' => 'gender',
            'placeholder' => 'Select Gender',
            'attr' => ['class' => 'searchable'],
        ]);
        $builder->add('employeeName', TextType::class);

        $builder->add('state', EntityType::class, [
            'label' => 'State',
            'class' => 'App:Masters\State',
            'placeholder' => '----Select State----',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('s')
                        ->orderBy('s.sortOrder', 'DESC')
                        ->where('s.isPublished=true');
            },
            'choice_label' => 'state',
            'attr' => ['class' => 'searchable ou_state', 'data-path' => $this->router->generate('portal_ou_district_loader')],
            'required' => true,
        ]);
        if ('O' == $regMode) {
            if (!('IsAlreadyRegistered' === $options['isRegistered'])) {
                $builder->add('mobileNumber', TextType::class);

                $builder->add('country', EntityType::class, [
                    'class' => 'App\Entity\Masters\Country',
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('e')
                                        ->orderBy('e.displayOrder', 'ASC');
                    },
                    'choice_label' => function ($ccode) {
                        $cName = $ccode->getCountryName();
                        $pCode = $ccode->getPhoneCode();

                        return '(+'.$pCode.') '.$cName;
                    },
                    'label' => 'Location',
                    'attr' => ['class' => 'searchable'],
                ]);
                    $builder->add('emailAddress', EmailType::class);
                    $builder->add('alternateEmailAddress', EmailType::class, ['required' => false]);
            } elseif (true === $options['is_mobile_edit_allowed']) {
                $builder->add('mobileNumber', TextType::class);

                $builder->add('country', EntityType::class, [
                    'class' => 'App\Entity\Masters\Country',
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('e')
                                        ->orderBy('e.displayOrder', 'ASC');
                    },
                    'choice_label' => function ($ccode) {
                        $cName = $ccode->getCountryName();
                        $pCode = $ccode->getPhoneCode();

                        return '(+'.$pCode.') '.$cName;
                    },
                    'label' => 'Location',
                    'attr' => ['class' => 'searchable'],
                ]);
            }
        } elseif (true === $options['is_mobile_edit_allowed']) {
            $builder->add('mobileNumber', TextType::class);

            $builder->add('country', EntityType::class, [
                'class' => 'App\Entity\Masters\Country',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('e')
                                    ->orderBy('e.displayOrder', 'ASC');
                },
                'choice_label' => function ($ccode) {
                    $cName = $ccode->getCountryName();
                    $pCode = $ccode->getPhoneCode();

                    return '(+'.$pCode.') '.$cName;
                },
                'label' => 'Location',
                'attr' => ['class' => 'searchable'],
            ]);
        }

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
                $state = (null === $data->getState() ? [] : $data->getState());
            } else {
                $ou = (null === $data ? [] : $data->getOrganizationUnit());
                $organization = (null === $data->getOrganizationUnit() ? [] : $data->getOrganizationUnit()->getOrganization());
                $ministry = (null === $data->getOrganizationUnit() ? [] : $data->getOrganizationUnit()->getOrganization()->getMinistry());
                $state = (null === $data->getState() ? [] : $data->getState());
            }

            $this->loadRoleWiseControls($form, $ministry, $organization, $role, $state);
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
                $state = (null === $data ? [] : $data['state']);
            } else {
                $ou = (null === $data ? [] : $data['organizationUnit']);
                $organization = (null === $data ? [] : $data['organization']);
                $ministry = (null === $data ? [] : $data['ministry']);
                $state = (null === $data ? [] : $data['state']);
            }

            $this->loadRoleWiseControls($form, $ministry, $organization, $role, $state);
        });
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
            'csrf_protection' => true, 'data_class' => Employee::class,
             'em' => null, 'profile' => null,
             'regMode' => null,
             'isRegistered' => null,
             'is_mobile_edit_allowed' => null,
        ]);
    }

    /**
     * Gets the unique name of this form.
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'Employee';
    }

    public function onPreSetData(FormEvent $event)
    {
        $form = $event->getForm();

        $data = $event->getData();
        if ('Y' == $data->getIsRegistered()) {
            $form->add('emailAddress', EmailType::class, [
                'required' => false,
                'mapped' => false,
            ]);
        }
    }

    private function loadRoleWiseControls($form, $ministry, $organization, $role, $state)
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
            'class' => 'App\Entity\Portal\OrganizationUnit',
            'placeholder' => 'Select  Organization Unit',
            'query_builder' => function (EntityRepository $repository) use ($organization) {
                return $qb = $repository->createQueryBuilder('ou')
                        ->where('ou.organization= :organization')
                        ->setParameter('organization', $organization);
            },
            'attr' => ['class' => 'searchable sbox-parentou'],
        ]);

        $form->add('designation', EntityType::class, [
            'class' => 'App:Portal\Designation',
            'choice_label' => 'DesignationName',
            'placeholder' => 'Select Designation',
            'attr' => ['class' => 'searchable sbox-dg'],
            'query_builder' => function (EntityRepository $repository) use ($organization) {
                $qb = $repository->createQueryBuilder('g')
                  ->where('g.organization = :ou')
                  ->addOrderBy('g.designationName')
                  ->setParameter('ou', $organization);

                return $qb;
            },
        ]);

        $form->add('district', EntityType::class, [
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
            'attr' => ['class' => 'searchable ou_district'],
        ]);
    }
}
