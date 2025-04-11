<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use App\Entity\Portal\Profile;

class ProfileWorkspace
{
    private $emr;
    private $security;

    public function __construct(EntityManagerInterface $em, Security $security)
    {
        $this->emr = $em;
        $this->security = $security;
    }

    public function getProfile()
    {
        $loggedUser = $this->security->getUser();
        // $selectedProfileWorkspace = $this->emr->getRepository('App:Portal\Profile')->findOneBy(['user' => $loggedUser, 'isCurrent' => true, 'isEnabled' => true]);
        $selectedProfile = $this->getSetCurrentProfile();

        return $selectedProfile;
    }

    public function getOU()
    {
        $loggedUser = $this->security->getUser();
        // $selectedProfile = $this->emr->getRepository('App:Portal\Profile')->findOneBy(['user' => $loggedUser, 'isCurrent' => true, 'isEnabled' => true]);
        $selectedProfile = $this->getSetCurrentProfile();

        return $selectedProfile->getOrganizationUnit();
    }

    public function getOrganization()
    {
        $loggedUser = $this->security->getUser();
        // $selectedProfile = $this->emr->getRepository('App:Portal\Profile')->findOneBy(['user' => $loggedUser, 'isCurrent' => true, 'isEnabled' => true]);
        $selectedProfile = $this->getSetCurrentProfile();

        return $selectedProfile->getOrganization();
    }

    public function getMinistry()
    {
        $loggedUser = $this->security->getUser();
        // $selectedProfile = $this->emr->getRepository('App:Portal\Profile')->findOneBy(['user' => $loggedUser, 'isCurrent' => true, 'isEnabled' => true]);
        $selectedProfile = $this->getSetCurrentProfile();

        return $selectedProfile->getMinistry();
    }

    public function getRole()
    {
        $loggedUser = $this->security->getUser();
        // $selectedProfile = $this->emr->getRepository('App:Portal\Profile')->findOneBy(['user' => $loggedUser, 'isCurrent' => true, 'isEnabled' => true]);
        $selectedProfile = $this->getSetCurrentProfile();

        return $selectedProfile->getRoles();
    }

    public function isXMPPRegistered()
    {
        $loggedUser = $this->security->getUser();
        $selectedEmployee = $this->emr->getRepository('App:Portal\Employee')->findOneBy(['user' => $loggedUser]);

        return $selectedEmployee->getIsRegistered();
    }

    public function getSetCurrentProfile()
    {
        // This may be useful if no current profile is found
        $loggedUser = $this->security->getUser();
        $selectedProfileWorkspace = $this->emr->getRepository('App:Portal\Profile')->findOneBy(['user' => $loggedUser, 'isEnabled' => true, 'isCurrent' => true]);
        if (!$selectedProfileWorkspace) {
            $selectedProfileWorkspace = $this->emr->getRepository('App:Portal\Profile')->findOneBy(['user' => $loggedUser, 'isEnabled' => true]);
            $selectedProfileWorkspace ? $selectedProfileWorkspace->setIsCurrent(true) : '';
        } 
        if (!$selectedProfileWorkspace) {
            $selectedProfileWorkspace = new Profile();
            $selectedProfileWorkspace->setUser($loggedUser);
            $uuid = \Ramsey\Uuid\Uuid::uuid4();
            $selectedProfileWorkspace->setGuid($uuid->toString());
            $selectedProfileWorkspace->setFromDate(new \DateTime('now'));
            $selectedProfileWorkspace->setIsEnabled(true);
            $selectedProfileWorkspace->setIsDefault(true);
            $selectedProfileWorkspace->setIsCurrent(true);
            $selectedProfileWorkspace->setIsAdditional(false);
            $role = $this->emr->getRepository("App:Portal\Roles")->findOneByRole('ROLE_MEMBER');
            $selectedProfileWorkspace->setRole($role);
        }
        $this->emr->persist($selectedProfileWorkspace);
        $this->emr->flush();
        return $selectedProfileWorkspace;
    }
}
