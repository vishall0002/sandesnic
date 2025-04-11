<?php

namespace App\Entity\Dashboard;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * MessageActivity
 *
 * @ORM\Table(name="report.message_activity", indexes={
 *              @ORM\Index(name="message_activity_date_hour_idx", columns={"date_hour"})
 * })
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class MessageActivity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_hour", type="datetime")
     */
    private $dateHour;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="message_count", type="integer")
     */
    private $messageCount;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateHour(): ?\DateTimeInterface
    {
        return $this->dateHour;
    }

    public function setDateHour(\DateTimeInterface $dateHour): self
    {
        $this->dateHour = $dateHour;

        return $this;
    }

    public function getMessageCount(): ?int
    {
        return $this->messageCount;
    }

    public function setMessageCount(int $messageCount): self
    {
        $this->messageCount = $messageCount;

        return $this;
    }
}
