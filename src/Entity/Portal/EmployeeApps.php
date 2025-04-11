<?php

namespace App\Entity\Portal;


use Doctrine\ORM\Mapping as ORM;
use App\Traits\MasterTrait;


/**
 * EmployeeApps.
 *
 * @ORM\Table(name="gim.employee_apps")
 * @ORM\Entity
 */

class EmployeeApps{

    use MasterTrait;

   /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\Employee")
     * @ORM\JoinColumn(name="emp_id", referencedColumnName="id")
     */
    private $employee;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\ExternalApps")
     * @ORM\JoinColumn(name="external_apps_id", referencedColumnName="id")
     */
    private $ExternalApps;

    /**
     * @ORM\Column(name="is_deleted" , type="boolean", nullable=true, options={"default"="false"})
     */
    private $isDeleted;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIsDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(?bool $isDeleted): self
    {
        $this->isDeleted = $isDeleted;

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

    public function getExternalApps(): ?ExternalApps
    {
        return $this->ExternalApps;
    }

    public function setExternalApps(?ExternalApps $ExternalApps): self
    {
        $this->ExternalApps = $ExternalApps;

        return $this;
    }



}
