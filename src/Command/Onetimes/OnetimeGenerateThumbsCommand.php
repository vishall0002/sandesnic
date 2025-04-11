<?php

namespace App\Command\Onetimes;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use App\Services\ImageProcess;
use App\Entity\Portal\FileDetail;


/**
 * This command mainly serve the purpose of notifying the department users regarding
 * a new upload is available mentioning Version Number.
 *
 * @author Amal
 */
class OnetimeGenerateThumbsCommand extends Command
{
    private $entityManager;
    private $imageProcess;

    public function __construct(EntityManagerInterface $em, ImageProcess $imageProcess)
    {
        parent::__construct();
        $this->imageProcess = $imageProcess;
        $this->entityManager = $em;
    }

    protected function configure()
    {
        $this->setName('app:onetime:generate:thumbs')
                ->setDescription('One time generation of thumbs')
                ->setHelp('app:onetime:generate:thumbs command :One time generation of thumbs')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->note(array(
            'One Time Import initialized....',
            'Please wait....',
        ));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $em = $this->entityManager;
        // $nullThumbs = $em->getRepository(FileDetail::Class)->findBy(['id' => 48]);
        $nullThumbs = $em->getRepository(FileDetail::Class)->findBy(['thumbnail' => null, 'fileType' => 'DP']);
        if (!$nullThumbs) {
            $io->warning('There is no data thumb generation');
        }
        $reqCount = 1;
        foreach ($nullThumbs as $nullThumb) {
            $io->success('Processing request no.'. $nullThumb->getId());
            $fileData = stream_get_contents($nullThumb->getFileData());
            $thumb = $this->imageProcess->generateThumbnail($fileData);
            $tsize = \strlen($fileData);
            if ($tsize < 3000) {
                $nullThumb->setThumbnail($fileData);
            } else {
                $nullThumb->setThumbnail($thumb);
            }
            
            $em->persist($nullThumb);
            $em->flush();
            echo 'Generated thumb successfully! '. $nullThumb->getId() .PHP_EOL;
            ++$reqCount;
            // die;
        }
    }
}
