<?php

namespace App\Security\Voter\Portal;

use App\Entity\Portal\Post;
use App\Entity\Portal\User;
use App\Entity\Portal\Employee;
use App\Services\ProfileWorkspace;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class EmployeeVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const DELETE = 'delete';

    private $security;
    private $profile;

    public function __construct(Security $security, ProfileWorkspace $profile)
    {
        $this->security = $security;
        $this->profile = $profile;
    }

    protected function supports(string $attribute, $subject)
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])) {
            return false;
        }

        // only vote on `Employee` objects
        if (!$subject instanceof Employee) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        /** @var Post $post */
        $employee = $subject;

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($employee, $user);
            case self::EDIT:
                return $this->canEdit($employee, $user);
            case self::DELETE:
                return $this->canDelete($employee, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canView(Employee $employee, User $user)
    {
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }
        // if they can edit, they can view
        if ($this->canEdit($employee, $user)) {
            return true;
        }
        $employeeOU = $employee->getOrganizationUnit();
        if ($this->profile->getOU() === $employeeOU) {
            return true;
        }

        if ($this->profile->getOrganization() === $employeeOU->getOrganization()) {
            return true;
        }

        if ($this->profile->getMinistry() === $employeeOU->getOrganization()->getMinistry()) {
            return true;
        }
        return false;
    }

    private function canEdit(Employee $employee, User $user)
    {

        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }
        $employeeOU = $employee->getOrganizationUnit();
        if ($this->profile->getOU() === $employeeOU) {
            return true;
        }

        if ($this->profile->getOrganization() === $employeeOU->getOrganization()) {
            return true;
        }

        if ($this->profile->getMinistry() === $employeeOU->getOrganization()->getMinistry()) {
            return true;
        }
        return false;
    }

    private function canDelete(Employee $employee, User $user)
    {
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }
        $employeeOU = $employee->getOrganizationUnit();
        if ($this->profile->getOU() === $employeeOU) {
            return true;
        }

        if ($this->profile->getOrganization() === $employeeOU->getOrganization()) {
            return true;
        }

        if ($this->profile->getMinistry() === $employeeOU->getOrganization()->getMinistry()) {
            return true;
        }
        return false;
    }
}
