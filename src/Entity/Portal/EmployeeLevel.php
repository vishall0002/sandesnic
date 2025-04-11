<?php

namespace App\Entity\Portal;

use Doctrine\ORM\Mapping as ORM;
use App\Traits\MasterTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * EmployeeLevel.
 *
 * @ORM\Table(name="gim.employee_level")
 * @UniqueEntity(
 *     fields={"guId"},
 *     errorPath="employeeLevelName",
 *     message="This is an invalid submission, please make sure validity"
 * )
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class EmployeeLevel
{
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
     * @var int
     *
     * @ORM\Column(name="level_no", type="integer")
     */
    private $levelNumber;

    /**
     * @var string
     * @Assert\NotBlank(message = "Employee Level Code is required")
     * @Assert\Regex(pattern="/[A-Za-z]+[0-9A-Za-z\s]*$/", match="true", message="Name should be alphanumeric and space. Only one space allowed.")
     * @ORM\Column(name="short_name", type="string", length=10, unique=true)
     */
    private $employeeLevelCode;

    /**
     * @var string
     * @Assert\NotBlank(message = "Employee Level Name is required")
     * @Assert\Regex(pattern="/[A-Za-z]+[0-9A-Za-z\s]*$/", match="true", message="Name should be alphanumeric and space. Only one space allowed.")
     * @ORM\Column(name="description", type="string", length=255)
     */
    private $employeeLevelName;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id",nullable=true)
     */
    private $organization;

    public function __toString()
    {
        return $this->employeeLevelName;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLevelNumber(): ?int
    {
        return $this->levelNumber;
    }

    public function setLevelNumber(int $levelNumber): self
    {
        $this->levelNumber = $levelNumber;

        return $this;
    }

    public function getEmployeeLevelCode(): ?string
    {
        return $this->employeeLevelCode;
    }

    public function setEmployeeLevelCode(string $employeeLevelCode): self
    {
        $this->employeeLevelCode = $employeeLevelCode;

        return $this;
    }

    public function getEmployeeLevelName(): ?string
    {
        return $this->employeeLevelName;
    }

    public function setEmployeeLevelName(string $employeeLevelName): self
    {
        $this->employeeLevelName = $employeeLevelName;

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
