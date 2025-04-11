<?php

namespace App\Entity\Portal;

use Doctrine\ORM\Mapping as ORM;

/**
 * Employee Group Admin.
 *
 * @ORM\Table(name="gim.employee_group_admins")
 * @ORM\Entity
 */
class EmployeeGroupAdmin
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
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\Group")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id")
     */
    private $group;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\Employee")
     * @ORM\JoinColumn(name="employee_id", referencedColumnName="id")
     */
    private $employee;
  
    /**
     * @ORM\ManyToOne(targetEntity="MetaData")
     * @ORM\JoinColumn(name="enable_metadata_id", referencedColumnName="id", nullable=true )
     */
    private $enableMetadata;

     /**
     * @ORM\ManyToOne(targetEntity="MetaData")
     * @ORM\JoinColumn(name="disable_metadata_id", referencedColumnName="id", nullable=true )
     */
    private $disableMetadata;

     /**
     * @ORM\Column(name="is_enabled" , type="boolean", nullable=true, options={"default"=false})
     */
    private $isEnabled;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIsEnabled(): ?bool
    {
        return $this->isEnabled;
    }

    public function setIsEnabled(?bool $isEnabled): self
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    public function getGroup(): ?Group
    {
        return $this->group;
    }

    public function setGroup(?Group $group): self
    {
        $this->group = $group;

        return $this;
    }

    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    public function setEmployee(?Employee $employee): self
    {
        $this->employee = $employee;

        return $this;
    }

    public function getEnableMetadata(): ?MetaData
    {
        return $this->enableMetadata;
    }

    public function setEnableMetadata(?MetaData $enableMetadata): self
    {
        $this->enableMetadata = $enableMetadata;

        return $this;
    }

    public function getDisableMetadata(): ?MetaData
    {
        return $this->disableMetadata;
    }

    public function setDisableMetadata(?MetaData $disableMetadata): self
    {
        $this->disableMetadata = $disableMetadata;

        return $this;
    }

    

   
}
