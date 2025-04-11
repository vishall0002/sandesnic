<?php

namespace App\Command;

use App\Entity\Portal\Profile;
use App\Entity\Portal\User;
use App\Services\DefaultValue;
use App\Services\PortalMetadata;
use App\Services\ProfileWorkspace;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Security\Encoder\SecuredLoginPasswordEncoder;

/**
 * This command mainly serve the purpose of notifying the department users regarding
 * a new upload is available mentioning Version Number.
 *
 * @author Vipin Bose
 */
class FixUserProfileCommand extends Command
{
    private $entityManager;
    private $profileWorkspace;
    private $metadata;
    private $defaultValue;
    

    public function __construct(EntityManagerInterface $em, DefaultValue $defVal, ProfileWorkspace $profileWorkspace, PortalMetadata $metadata, SecuredLoginPasswordEncoder $password_encoder)
    {
        parent::__construct();
        $this->profileWorkspace = $profileWorkspace;
        $this->metadata = $metadata;
        $this->defaultValue = $defVal;
        
        $this->entityManager = $em;
        $this->password_encoder = $password_encoder;
    }

    protected function configure()
    {
        $this->setName('app:fix-user-profile')
                ->setDescription('Fixing user and profile of auto on-boarded data')
                ->setHelp('fix-user-profile : Fixing user profile of users automatically on-boarded from Digital');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        // $io = new SymfonyStyle($input, $output);
        // $io->note(array(
        //     'Lapse process initialize....',
        //     'Please wait....',
        // ));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->entityManager;
        $myCon = $em->getConnection();

        $qryE2I = $myCon->prepare('select * from gim.employee where lower(email) not in (select email_canonical from gim.portal_users)');
        $qryE2I->execute();
        $emp2Is = $qryE2I->fetchAll();
        foreach ($emp2Is as $emp2I) {
            $em->getConnection()->beginTransaction();
            try {
                $employeeEmail = $emp2I['email'];
                $employee = $em->getRepository('App:Portal\Employee')->findOneBy(['emailAddress' => $employeeEmail]);

                $newUser = new User();
                $newUser->setRoles(['ROLE_MEMBER']);
                $role = $em->getRepository("App:Portal\Roles")->findOneByRole('ROLE_MEMBER');
                $newUser->setUsername($employeeEmail);
                $the_salt = password_hash(uniqid(null, true), PASSWORD_BCRYPT);
                $newUser->setSalt($the_salt);
                $the_password = $this->password_encoder->encodePassword('nic*123', $the_salt);
                $newUser->setPassword($the_password);
                $newUser->setIsFcp(false);
                $newUser->setEnabled(true);
                $newUser->setEmail($employeeEmail);
                $employee->setUser($newUser);

                $profile = new Profile();
                $profile->setUser($newUser);
                $uuid = \Ramsey\Uuid\Uuid::uuid4();
                $profile->setGuid($uuid->toString());
                $profile->setFromDate(new \DateTime('now'));
                $profile->setIsEnabled(true);
                $profile->setIsDefault(true);
                $profile->setIsCurrent(true);
                $profile->setIsAdditional(false);
                $profile->setInsertMetaData($this->metadata->getPortalMetadata('I'));
                $profile->setRole($role);
                $profile->setOrganizationUnit($employee->getOrganizationUnit());
                $profile->setOrganization($employee->getOrganizationUnit()->getOrganization());
                $profile->setMinistry($employee->getOrganizationUnit()->getOrganization()->getMinistry());

                $em->persist($newUser);
                $em->persist($employee);
                $em->persist($profile);
                $em->flush();

                echo 'SUCCESS - User successfully imported '.$employeeEmail.PHP_EOL;
                $em->getConnection()->commit();
            } catch (Exception $ex) {
                $em->getConnection()->rollback();
                echo 'FAIL - An error has been occurred for this employee '.$employeeEmail.PHP_EOL;
            }
        }
        echo 'Completed';
    }
}
