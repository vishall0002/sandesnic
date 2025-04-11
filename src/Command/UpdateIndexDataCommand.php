<?php
// src/Command/UpdateIndexDataCommand.php

namespace App\Command;

use Doctrine\DBAL\FetchMode;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use App\Services\XMPPGeneral;
use Symfony\Component\HttpKernel\KernelInterface;

class UpdateIndexDataCommand extends Command
{
    protected static $defaultName = 'app:update-index-data';

    private $entityManager;
    private $kernel;
    public function __construct(EntityManagerInterface $entityManager,XMPPGeneral $xmpp,KernelInterface $kernel)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->xmppGeneral = $xmpp;
        $this->kernel = $kernel;
    }

    protected function configure()
    {
        $this
            ->setDescription('Update index page data JSON file');
    }




    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        $em = $this->entityManager;
        $dbCon = $em->getConnection();
        $ministry = 0;
        $organization = 0;
        $qryMA = $em->createQuery("SELECT SUM(ma.messageCount),  MAX(ma.dateHour) FROM App:Dashboard\MessageActivity ma");
        $qryMACount = $qryMA->getResult();
        
        // Message Activity Count
        $qryMA = $dbCon->prepare("SELECT SUM(ma.message_count),  MAX(ma.date_hour) FROM report.message_activity_org as ma INNER JOIN gim.organization as o ON ma.organization_id = o.id WHERE o.ministry_id = :ministry OR :ministry = 0");
        $qryMA->bindValue('ministry', $ministry);
        $qryMA->execute();
        $qryMACounts = $qryMA->fetchAll(FetchMode::NUMERIC);
        $qryMACount = $qryMACounts[0][0];
        $updateTime = $qryMACounts[0][1];

        // Registration Count
        $qrychat = $dbCon->prepare("SELECT COUNT(1) FROM gim.employee as e WHERE e.registered = 'Y'");
        $qrychat->execute();
        $qryRegistrationCount = $qrychat->fetchAll(FetchMode::NUMERIC)[0];

      
        // Organization Count
        $qrychat = $dbCon->prepare("SELECT COUNT(1) FROM gim.organization as o  WHERE o.ministry_id = :ministry OR :ministry = 0");
        $qrychat->bindValue(':ministry', $ministry);
        $qrychat->execute();
        $qryOrganizationCount = $qrychat->fetchAll(FetchMode::NUMERIC)[0];

        //Egove Message Count
        $chatStatistics = $dbCon->prepare("select SUM(message_count) as egove from report.app_message_activity");
        $chatStatistics->execute();
        $qryegovMessageCount = $chatStatistics->fetchAll(FetchMode::NUMERIC)[0];

        
       
        
        // Fetch data from queries and format it as an array
        $data = [
            'OCount' => $qryOrganizationCount,
            'LAU' => $updateTime,
            'ERCount' => $qryRegistrationCount,
            'MCount' => $qryMACount,
            'egovemessage' =>$qryegovMessageCount
          
        ];

        // Convert data to JSON format
        $jsonData = json_encode($data);
        // Write JSON data to a file
        $projectDir = $this->kernel->getProjectDir();
        // Construct the file path
        $jsonFilePath = $projectDir . '/data.json';
        $filesystem = new Filesystem();
        $filesystem->dumpFile($jsonFilePath, $jsonData);
        $output->writeln('Json File data updated successfully.');

        $this->storeMonthlyMessageCount($output);
        // $this->storeOnboardedMessageStats($output);  commenting this 
        
         return 1;
    }



    private function storeMonthlyMessageCount_v(OutputInterface $output)
    {
        $em = $this->entityManager;
        $dbCon = $em->getConnection();

        // Fetch monthly message count (grouped by year and month)
        $stmt = $dbCon->prepare("
            SELECT 
                TO_CHAR(date_hour, 'YYYY-MM') as month,
                SUM(message_count) as total_messages
            FROM report.message_activity_org
            GROUP BY month
            ORDER BY month DESC
            LIMIT 12
        ");
        $stmt->execute();
        $monthlyData = $stmt->fetchAll(FetchMode::ASSOCIATIVE);

        // Convert to JSON
        $jsonData = json_encode($monthlyData);

        // Save to a new file
        $projectDir = $this->kernel->getProjectDir();
        $jsonFilePath = $projectDir . '/MonthlymessageCountdata.json';
        $filesystem = new \Symfony\Component\Filesystem\Filesystem();
        $filesystem->dumpFile($jsonFilePath, $jsonData);

        $output->writeln('Monthly message count data updated successfully in MonthlymessageCountdata.json');
    }


    private function storeMonthlyMessageCount_diff_query(OutputInterface $output)
    {
        $em = $this->entityManager;
        $dbCon = $em->getConnection();

        $stmtOrg = $dbCon->prepare("
            SELECT 
                TO_CHAR(date_hour, 'YYYY-MM') as month,
                SUM(message_count) as organization
            FROM report.message_activity_org
            GROUP BY month
            ORDER BY month DESC
            LIMIT 12
        ");
        $stmtOrg->execute();
        $orgData = $stmtOrg->fetchAll(\PDO::FETCH_ASSOC);

        $stmtEgov = $dbCon->prepare("
            SELECT 
                TO_CHAR(date_hour, 'YYYY-MM') as month,
                SUM(message_count) as egovapp
            FROM report.app_message_activity
            GROUP BY month
            ORDER BY month DESC
            LIMIT 12
        ");
        $stmtEgov->execute();
        $egovData = $stmtEgov->fetchAll(\PDO::FETCH_ASSOC);

        $mergedData = [];

        foreach ($orgData as $row) {
            $mergedData[$row['month']]['month'] = $row['month'];
            $mergedData[$row['month']]['organization'] = (int)$row['organization'];
            $mergedData[$row['month']]['egovapp'] = 0;
        }

        foreach ($egovData as $row) {
            if (!isset($mergedData[$row['month']])) {
                $mergedData[$row['month']]['month'] = $row['month'];
                $mergedData[$row['month']]['organization'] = 0;
            }
            $mergedData[$row['month']]['egovapp'] = (int)$row['egovapp'];
        }

        $finalData = array_values($mergedData);

        // Save to JSON
        $jsonData = json_encode($finalData);
        $projectDir = $this->kernel->getProjectDir();
        $jsonFilePath = $projectDir . '/MonthlymessageCountdata.json';

        $filesystem = new \Symfony\Component\Filesystem\Filesystem();
        $filesystem->dumpFile($jsonFilePath, $jsonData);

        $output->writeln('Monthly message count (org + egov) updated successfully in MonthlymessageCountdata.json');
    }

    private function storeMonthlyMessageCount(OutputInterface $output)
    {
        $em = $this->entityManager;
        $dbCon = $em->getConnection();

        // Combined query for organization and egovapp monthly message count
        $stmt = $dbCon->prepare("
            SELECT 
                COALESCE(org.month, egov.month) as month,
                COALESCE(org.organization, 0) as organization,
                COALESCE(egov.egovapp, 0) as egovapp
            FROM (
                SELECT 
                    TO_CHAR(date_hour, 'YYYY-MM') as month,
                    SUM(message_count) as organization
                FROM report.message_activity_org
                GROUP BY month
            ) as org
            FULL OUTER JOIN (
                SELECT 
                    TO_CHAR(date_hour, 'YYYY-MM') as month,
                    SUM(message_count) as egovapp
                FROM report.app_message_activity
                GROUP BY month
            ) as egov
            ON org.month = egov.month
            ORDER BY month DESC
            LIMIT 12
        ");

        $stmt->execute();
        $monthlyData = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Convert to JSON
        $jsonData = json_encode($monthlyData, JSON_PRETTY_PRINT);

        // Save to MonthlymessageCountdata.json
        $projectDir = $this->kernel->getProjectDir();
        $jsonFilePath = $projectDir . '/MonthlymessageCountdata.json';

        $filesystem = new \Symfony\Component\Filesystem\Filesystem();
        $filesystem->dumpFile($jsonFilePath, $jsonData);

        $output->writeln('Monthly message count data saved to MonthlymessageCountdata.json');
    }


    private function storeOnboardedMessageStats(OutputInterface $output)
    {
        $em = $this->entityManager;
        $dbCon = $em->getConnection();

        // -- 1. Message count by organization category --
        $stmtOrg = $dbCon->prepare("
            SELECT 
                COALESCE(o.category, 'others') as category,
                SUM(ma.message_count) as total_messages
            FROM report.message_activity_org ma
            INNER JOIN gim.organization o ON ma.organization_id = o.id
            GROUP BY o.category
        ");
        $stmtOrg->execute();
        $orgData = $stmtOrg->fetchAll(\PDO::FETCH_ASSOC);

        // -- 2. Message count by egov app category --
        $stmtEgov = $dbCon->prepare("
            SELECT 
                COALESCE(a.category, 'others') as category,
                SUM(ma.message_count) as total_messages
            FROM report.app_message_activity ma
            INNER JOIN gim.application a ON ma.application_id = a.id
            GROUP BY a.category
        ");
        $stmtEgov->execute();
        $egovData = $stmtEgov->fetchAll(\PDO::FETCH_ASSOC);

        // Convert to associative arrays with category as key
        $orgStats = [];
        foreach ($orgData as $row) {
            $orgStats[$row['category']] = (int) $row['total_messages'];
        }

        $egovStats = [];
        foreach ($egovData as $row) {
            $egovStats[$row['category']] = (int) $row['total_messages'];
        }

        // Final combined output
        $finalData = [
            'Organizations' => $orgStats,
            'eGovApplications' => $egovStats
        ];

        // Write to JSON
        $projectDir = $this->kernel->getProjectDir();
        $jsonFilePath = $projectDir . '/OnboardedMessageStats.json';

        $filesystem = new \Symfony\Component\Filesystem\Filesystem();
        $filesystem->dumpFile($jsonFilePath, json_encode($finalData, JSON_PRETTY_PRINT));

        $output->writeln('Onboarded organization and eGov application message counts saved to OnboardedMessageStats.json');
    }


}
