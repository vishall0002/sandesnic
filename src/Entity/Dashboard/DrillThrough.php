<?php

namespace App\Entity\Dashboard;

use App\Entity\Portal\OrganizationUnit;
use Doctrine\ORM\Mapping as ORM;

/**
 * DrillThrough.
 *
 * @ORM\Table(name="report.drill_throughs")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class DrillThrough
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
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\OrganizationUnit")
     * @ORM\JoinColumn(name="ou_id", referencedColumnName="ou_id")
     */
    private $organizationUnit;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="report_date", type="datetime")
     */
    private $reportDate;

    /**
     * @var int
     *
     * @ORM\Column(name="onboarded_count", type="integer",nullable=true)
     */
    private $onboardedCount;

    /**
     * @var int
     *
     * @ORM\Column(name="registered_count", type="integer",nullable=true)
     */
    private $registeredCount;

    /**
     * @var int
     *
     * @ORM\Column(name="group_count", type="integer",nullable=true)
     */
    private $groupCount;

    /**
     * @var int
     *
     * @ORM\Column(name="active_users", type="integer",nullable=true)
     */
    private $activeUsers;

    /**
     * @var int
     *
     * @ORM\Column(name="total_messages", type="integer",nullable=true)
     */
    private $totalMessages;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_time", type="datetime",nullable=true)
     */
    private $updateTime;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReportDate(): ?\DateTimeInterface
    {
        return $this->reportDate;
    }

    public function setReportDate(\DateTimeInterface $reportDate): self
    {
        $this->reportDate = $reportDate;

        return $this;
    }

    public function getOnboardedCount(): ?int
    {
        return $this->onboardedCount;
    }

    public function setOnboardedCount(int $onboardedCount): self
    {
        $this->onboardedCount = $onboardedCount;

        return $this;
    }

    public function getRegisteredCount(): ?int
    {
        return $this->registeredCount;
    }

    public function setRegisteredCount(int $registeredCount): self
    {
        $this->registeredCount = $registeredCount;

        return $this;
    }

    public function getGroupCount(): ?int
    {
        return $this->groupCount;
    }

    public function setGroupCount(int $groupCount): self
    {
        $this->groupCount = $groupCount;

        return $this;
    }

    public function getActiveUsers(): ?int
    {
        return $this->activeUsers;
    }

    public function setActiveUsers(int $activeUsers): self
    {
        $this->activeUsers = $activeUsers;

        return $this;
    }

    public function getTotalMessages(): ?int
    {
        return $this->totalMessages;
    }

    public function setTotalMessages(int $totalMessages): self
    {
        $this->totalMessages = $totalMessages;

        return $this;
    }

    public function getOrganizationUnit(): ?OrganizationUnit
    {
        return $this->organizationUnit;
    }

    public function setOrganizationUnit(?OrganizationUnit $organizationUnit): self
    {
        $this->organizationUnit = $organizationUnit;

        return $this;
    }

    public function getUpdateTime(): ?\DateTimeInterface
    {
        return $this->updateTime;
    }

    public function setUpdateTime(\DateTimeInterface $updateTime): self
    {
        $this->updateTime = $updateTime;

        return $this;
    }
}
