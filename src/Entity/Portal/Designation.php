<?php

namespace App\Entity\Portal;

use App\Entity\Portal\Organization;
use App\Entity\Portal\MetaData;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Traits\MasterTrait;

/**
 * Designation
 *
 * @ORM\Table(name="gim.designation")
 * @ORM\Entity
 * @UniqueEntity("designationCode")
 * @UniqueEntity(
 *     fields={"guId"},
 *     errorPath="designationName",
 *     message="This is an invalid submission, please make sure validity"
 * )
 * @ORM\HasLifecycleCallbacks
 */
class Designation
{
    use MasterTrait;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", options={"comment":"Designation Id"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * 
     * @ORM\Column(name="designation_code", type="string", length=50)
     */
    private $designationCode;

    /**
     * @var string
     * @Assert\NotBlank(message = "Designation Name is required")
     * @Assert\Regex(pattern="/[A-Za-z]+[0-9A-Za-z\s]*$/", match="true", message="Name should be alphanumeric and space. Only one space allowed.")
     * @ORM\Column(name="designation_name", type="string", length=255, options={"comment":"Designation Name"})
     */
    private $designationName;


    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id",nullable=true)
     */
    private $organization;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDesignationCode(): ?string
    {
        return $this->designationCode;
    }

    public function setDesignationCode(string $designationCode): self
    {
        $this->designationCode = $designationCode;

        return $this;
    }

    public function getDesignationName(): ?string
    {
        return $this->designationName;
    }

    public function setDesignationName(string $designationName): self
    {
        $this->designationName = $designationName;

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
