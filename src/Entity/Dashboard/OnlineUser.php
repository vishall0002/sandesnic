<?php

namespace App\Entity\Dashboard;

use Doctrine\ORM\Mapping as ORM;

/**
 * OnlineUser.
 *
 * @ORM\Table(name="report.active_user_log")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class OnlineUser
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="log_time", type="datetime")
     */
    private $logTime;

    /**
     * @var int
     *
     * @ORM\Column(name="cnt", type="integer")
     */
    private $userCount;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLogTime(): ?\DateTimeInterface
    {
        return $this->logTime;
    }

    public function setLogTime(\DateTimeInterface $logTime): self
    {
        $this->logTime = $logTime;

        return $this;
    }

    public function getUserCount(): ?int
    {
        return $this->userCount;
    }

    public function setUserCount(int $userCount): self
    {
        $this->userCount = $userCount;

        return $this;
    }
}
