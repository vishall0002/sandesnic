<?php

namespace App\Entity\Portal;

use Doctrine\ORM\Mapping as ORM;

/**
 * Employee.
 *
 * @ORM\Table(name="gim.employee_migration_statuses")
 * @ORM\Entity
 */
class EmployeeMigrationStatus
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"comment"="Id"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\Employee")
     * @ORM\JoinColumn(name="employee_id", referencedColumnName="id", nullable=true)
     */
    private $employee;

    /**
     * @ORM\Column(name="request_id", type="integer")
     */
    private $requestId;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRequestId(): ?int
    {
        return $this->requestId;
    }

    public function setRequestId(int $requestId): self
    {
        $this->requestId = $requestId;

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

    
}
