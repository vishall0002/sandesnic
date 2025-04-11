<?php

namespace App\Command\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * This command mainly serve the purpose of notifying the department users regarding
 * a new upload is available mentioning Version Number.
 *
 * @author Vipin Bose
 */
class SymfonyMailerNICMailCommand extends Command
{
    private $entityManager;
    private $mailer;

    public function __construct(EntityManagerInterface $em, MailerInterface $mailer)
    {
        parent::__construct();
        $this->entityManager = $em;
        $this->mailer = $mailer;
    }

    protected function configure()
    {
        $this->setName('app:test-mailer')
                ->setDescription('Test mailer command')
                ->setHelp('app:test-mailer command: Send Mailer Command');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $email = (new Email())
        ->from('bose.vipin@nic.in')
        ->to('vipin.bose@gov.in')
        //->cc('cc@example.com')
        //->bcc('bcc@example.com')
        //->replyTo('fabien@example.com')
        //->priority(Email::PRIORITY_HIGH)
        ->subject('Time for Symfony Mailer!')
        ->text('Sending emails is fun again!')
        ->html('<p>See Twig integration for better HTML integration!</p>')
        ->attachFromPath('/home/vipin/Desktop/commitoverwrite.jpeg');

        $sentEmail = $this->mailer->send($email);
    }
}
