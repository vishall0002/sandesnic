<?php

namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use App\Services\ProfileWorkspace;
use App\Services\PortalMetadata;

use App\Entity\Portal\Profile;

/**
 * This command mainly serve the purpose of notifying the department users regarding
 * a new upload is available mentioning Version Number.
 *
 * @author Amal
 */
class GenerateUserProfileCommand extends Command {

    private $entityManager;
    private $profileWorkspace;
    private $metadata;
    

    public function __construct(EntityManagerInterface $em, ProfileWorkspace $profileWorkspace, PortalMetadata $metadata) {
        parent::__construct();
        $this->profileWorkspace = $profileWorkspace;
        
        $this->entityManager = $em;
        $this->metadata = $metadata;
    }

    protected function configure() {
        $this->setName('app:generate-user-profile')
                ->setDescription('Generate user profile')
                ->setHelp('app:generate-role command : This may be used in the command line once to generate profiles from users with multiple roles')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output) {
        $io = new SymfonyStyle($input, $output);
        $io->note(array(
            'Process initialized....',
            'Please wait....',
        ));
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $io = new SymfonyStyle($input, $output);
        $em = $this->entityManager;
        $em->getConnection()->beginTransaction();
        try {
            $users = $em->getRepository('App:Portal\User')->findAll();
            foreach ($users as $user) {
//                echo 'current user id ' . $user->getId() . PHP_EOL;
                $roles = $user->getRoles();
//                var_dump($roles);
//                if (count($roles) > 1) {
                if (count($roles) > 3) {
                    foreach ($roles as $role) {
//                        echo 'current role ' . $role . PHP_EOL;
                        if ($role != 'ROLE_USER' && $role != 'ROLE_MEMBER') {
                            $roleObj = $em->getRepository("App:Portal\Roles")->findOneByRole($role);
                            $profile = $em->getRepository("App:Portal\Profile")->findOneBy(['user' => $user, 'role' => $roleObj]);
                            $oU = $em->getRepository("App:Portal\Profile")->findOneBy(['user' => $user])->getOrganizationUnit();
                            if (!$profile) {
                                $metada = $this->metadata->getPortalMetadata('I');
                                $uuid = \Ramsey\Uuid\Uuid::uuid4();
                                $profile = new Profile();
                                $profile->setUser($user);
                                $profile->setGuid($uuid);
                                $profile->setFromDate(new \DateTime('now'));
                                $profile->setIsEnabled(true);
                                $profile->setIsDefault(false);
                                $profile->setIsCurrent(false);
                                $profile->setIsAdditional(true);
                                $profile->setInsertMetaData($metada);
                                $profile->setRole($roleObj);
                                if ($oU) {
                                    $profile->setOrganizationUnit($oU);
                                    $profile->setOrganization($oU->getOrganization());
                                    $profile->setMinistry($oU->getOrganization()->getMinistry());
                                }
                                $em->persist($profile);
                                echo 'created profile for user id ' . $user->getId() . PHP_EOL;
                            } else {
                                $user->setRoles(['ROLE_MEMBER', $role]);
                                $userManager = $this->fosUserManager;
                                $userManager->updateUser($user, true);
                            }
                        }
                    }
                }
            }
            $em->flush();
            $em->getConnection()->commit();
            $io->success('Success');
        } catch (Exception $ex) {
            $em->getConnection()->rollback();
            $io->warning($ex->getMessage());
        }
    }

}
