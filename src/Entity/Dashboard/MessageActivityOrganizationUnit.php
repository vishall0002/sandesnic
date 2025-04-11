<?php

namespace App\Entity\Dashboard;

use App\Entity\Portal\Organization;
use App\Entity\Portal\OrganizationUnit;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * MessageActivityOrganizationUnit
 *
 * @ORM\Table(name="report.message_activity_ou", indexes={@ORM\Index(name="message_activity_ou_ou_id_dh_idx", columns={"date_hour", "ou_id"})}))
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class MessageActivityOrganizationUnit
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

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\OrganizationUnit")
     * @ORM\JoinColumn(name="ou_id", referencedColumnName="ou_id")
     */
    private $organizationUnit;
    
    /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Portal\Organization")
    * @ORM\JoinColumn(name="organization_id", referencedColumnName="id")
    */
    private $organization;

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

    public function getOrganizationUnit(): ?OrganizationUnit
    {
        return $this->organizationUnit;
    }

    public function setOrganizationUnit(?OrganizationUnit $organizationUnit): self
    {
        $this->organizationUnit = $organizationUnit;

        return $this;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

}
