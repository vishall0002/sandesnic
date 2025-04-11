<?php

namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use App\Services\ProfileWorkspace;
use App\Services\PortalMetadata;

use App\Entity\Portal\Designation;
use App\Entity\Portal\EmployeeLevel;

/**
 * This command mainly serve the purpose of notifying the department employees regarding
 * a new upload is available mentioning Version Number.
 *
 * @author Amal
 */
class FixDesignationLevelCommand extends Command
{
    private $entityManager;
    private $profileWorkspace;
    private $metadata;
    

    public function __construct(EntityManagerInterface $em, ProfileWorkspace $profileWorkspace, PortalMetadata $metadata)
    {
        parent::__construct();
        $this->profileWorkspace = $profileWorkspace;
        
        $this->entityManager = $em;
        $this->metadata = $metadata;
    }

    protected function configure()
    {
        $this->setName('app:fix-designation-level')
                ->setDescription('Generate user profile')
                ->setHelp('app:generate-role command : This may be used in the command line once to generate profiles from employees with multiple roles')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->note(array(
            'Process initialized....',
            'Please wait....',
        ));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $em = $this->entityManager;
        // $employees = $em->getRepository('App:Portal\Employee')->findBy(array('emailAddress' => 'k.mukundan@meity.gov.in'));
        // $employees = $em->getRepository('App:Portal\Employee')->findBy(array('emailAddress' => 'amishra.edu@nic.in'));
        $employees = $em->getRepository('App:Portal\Employee')->findAll();
        foreach ($employees as $employee) {
            $em->getConnection()->beginTransaction();
            try {
                echo $employee->getEmployeeName().' Processing '.PHP_EOL;
                $empDesignation = $employee->getDesignation();
                $empLevel = $employee->getEmployeeLevelID();
                $empOrganization = $employee->getOrganizationUnit()->getOrganization();
                $empEmail = $employee->getEmailAddress();
                echo $empEmail.' Processing '.PHP_EOL;
                $desigOrganization = $empDesignation->getOrganization();
                $levelOrganization = $empLevel->getOrganization();

                if ($empOrganization === $desigOrganization) {
                    echo $empEmail.' Designation no anomaly '.PHP_EOL;
                } else {
                    $designationExists = $em->getRepository("App:Portal\Designation")->findOneBy(['id' => $empDesignation->getId(), 'organization' => $empOrganization]);
                    if (!$designationExists) {
                        echo $empEmail.' Designation anomaly found'.PHP_EOL;
                        $newDesignation = new Designation();
                        $uuid = \Ramsey\Uuid\Uuid::uuid4();
                        $newDesignation->setGuId($uuid->toString());
                        $newDesignation->setOrganization($empOrganization);
                        $newDesignation->setDesignationCode($empDesignation->getDesignationCode());
                        $newDesignation->setDesignationName($empDesignation->getDesignationName());
                        $em->persist($newDesignation);
                        $employee->setDesignation($newDesignation);
                        echo $empEmail.' Designation anomaly found, new designation created '.$empDesignation->getDesignationName().PHP_EOL;
                    } else {
                        echo $empEmail.' Designation anomaly found, but designation  exists '.$empDesignation->getDesignationName().PHP_EOL;
                        $employee->setDesignation($designationExists);
                    }
                    $em->persist($employee);
                    $em->flush();
                }
                if ($empOrganization === $levelOrganization) {
                    echo $empEmail.' Level no anomaly '.PHP_EOL;
                } else {
                    $dbLC = substr($empOrganization->getId().'-'.$empLevel->getEmployeeLevelCode(),0,10);
                    if (!$dbLC){
                        echo $empOrganization->getId();
                        echo $empLevel->getEmployeeLevelCode();
                        die;
                    }
                    $levelExists = $em->getRepository("App:Portal\EmployeeLevel")->findOneBy(['employeeLevelCode' => $dbLC, 'organization' => $empOrganization]);
                    if (!$levelExists) {
                        $newEmployeeLevel = new EmployeeLevel();
                        $uuid = \Ramsey\Uuid\Uuid::uuid4();
                        $newEmployeeLevel->setGuId($uuid->toString());
                        $newEmployeeLevel->setOrganization($empOrganization);
                        $newEmployeeLevel->setEmployeeLevelCode($dbLC);
                        $newEmployeeLevel->setEmployeeLevelName($empLevel->getEmployeeLevelName());
                        $newEmployeeLevel->setLevelNumber($empLevel->getLevelNumber());
                        $em->persist($newEmployeeLevel);
                        $employee->setEmployeeLevel($newEmployeeLevel->getLevelNumber());
                        $employee->setEmployeeLevelID($newEmployeeLevel);
                        echo $empEmail.' Level anomaly found - New level created '.$empLevel->getEmployeeLevelName().PHP_EOL;
                    } else {
                        echo $empEmail.' Level anomaly found But level exists '.$empLevel->getEmployeeLevelName().PHP_EOL;
                        $employee->setEmployeeLevel($levelExists->getLevelNumber());
                        $employee->setEmployeeLevelID($levelExists);
                    }
                    $em->persist($employee);
                    $em->flush();
                }
                $em->getConnection()->commit();
            } catch (Exception $ex) {
                $em->getConnection()->rollback();
                $io->warning($ex->getMessage());
            }
        }
        $io->success('Success');
    }
}
