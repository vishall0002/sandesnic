<?php

namespace App\Entity\Portal;

use App\Entity\Masters\District;
use App\Entity\Masters\OrganizationType;
use App\Entity\Masters\State;
use Doctrine\ORM\Mapping as ORM;
use App\Traits\MasterTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @Assert\GroupSequence({"OrganizationUnit", "Length", "Regex"})
 * @UniqueEntity(
 *     fields={"guId"},
 *     errorPath="OUName",
 *     message="This is an invalid submission, please make sure validity"
 * )
 * @ORM\Table(name="gim.organization_unit")
 * @ORM\Entity()
 */
class OrganizationUnit
{
    use MasterTrait;
    /**
     * @ORM\Column(name="ou_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="ou_code", type="string", length=100)
     */
    private $OUCode;

    /**
     * @Assert\NotBlank(message = "Data required")
     * @Assert\Length(
     *      min = 3,
     *      max = 100,
     *      minMessage = "The Name  must be at least {{ limit }} characters long",
     *      maxMessage = "The Name cannot be longer than {{ limit }} characters long",
     *      groups={"Length"})
     * @Assert\Regex(
     *      pattern     = "/^[A-Za-z,-_. 0-9]+$/i",
     *      message = "Enter a proper data, allowed only Characters, Digits, hiphen(-), us(_), dot(.) and spaces.",
     *      groups={"Regex"})
     * @ORM\Column(name="ou_name", type="string", length=255, nullable=false)
     */
    private $OUName;

    /**
     * @ORM\ManyToOne(targetEntity="OrganizationUnit")
     * @ORM\JoinColumn(name="parent_ou", referencedColumnName="ou_id", nullable=true)
     */
    private $parentOrganizationUnit;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Masters\OrganizationType")
     * @ORM\JoinColumn(name="ou_type", referencedColumnName="code")
     */
    private $organizationUnitType;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id")
     */
    private $organization;

    /**
     * @Assert\NotBlank(message = "Data required")
     * @Assert\Length(
     *      min = 3,
     *      max = 100,
     *      minMessage = "The Name  must be at least {{ limit }} characters long",
     *      maxMessage = "The Name cannot be longer than {{ limit }} characters long",
     *      groups={"Length"})
     * @Assert\Regex(
     *      pattern     = "/^[A-Za-z,-_.() 0-9]+$/i",
     *      message = "Enter a proper data, allowed only Characters, Digits, hiphen(-), us(_), dot(.) and spaces.",
     *      groups={"Regex"})
     * @ORM\Column(name="ou_address", type="string", length=200, nullable=true)
     */
    private $address;

    /**
     * @Assert\NotBlank(message = "Data required")
     * @ORM\ManyToOne(targetEntity="App\Entity\Masters\State")
     * @ORM\JoinColumn(name="state_id", referencedColumnName="id", nullable=true)
     */
    private $state;

    /**
     * @Assert\NotBlank(message = "Data required")
     * @ORM\ManyToOne(targetEntity="App\Entity\Masters\District")
     * @ORM\JoinColumn(name="district_id", referencedColumnName="id", nullable=true)
     */
    private $district;

    /**
     * @Assert\Length(
     *      min = 6,
     *      max = 6,
     *      minMessage = "The data  must be at least {{ limit }} digits long",
     *      maxMessage = "The data cannot be longer than {{ limit }} characters digits long",
     *      groups={"Length"})
     * @Assert\Regex(
     *     pattern     = "/^(0)?[0-9]{6}$/i",
     *     message = "Enter a proper data, only digits are allowed.",
     *     groups={"Regex"})
     * @ORM\Column(name="pin_code",type="string", length=6, nullable=true)
     */
    private $pinCode;

    /**
     * @Assert\Length(
     *      min = 10,
     *      max = 11,
     *      minMessage = "The data  must be at least {{ limit }} digits long",
     *      maxMessage = "The data cannot be longer than {{ limit }} characters digits long",
     *      groups={"Length"})
     * @Assert\Regex(
     *     pattern     = "/^(0)?[0-9]{11}$/i",
     *     message = "Enter a proper data, only digits are allowed.",
     *     groups={"Regex"})
     * @ORM\Column(name="landline",type="string", length=11, nullable=true);
     */
    private $landline;

    /**
     * @Assert\Url(
     *    message = "The url '{{ value }}' is not a valid url",
     * )
     * @ORM\Column(name="website",type="string", length=50,unique=true, nullable=true)
     */
    private $website;
    
    /**
     * @ORM\Column(name="is_offboarders" , type="boolean", nullable=true, options={"default"=false})
     */
    private $isOffBoarders;


    public function __toString()
    {
        return $this->OUName;
    }
    public function __construct()
    {
        $this->isPublished = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOUCode(): ?string
    {
        return $this->OUCode;
    }

    public function setOUCode(string $OUCode): self
    {
        $this->OUCode = $OUCode;

        return $this;
    }

    public function getOUName(): ?string
    {
        return $this->OUName;
    }

    public function setOUName(string $OUName): self
    {
        $this->OUName = $OUName;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getPinCode(): ?string
    {
        return $this->pinCode;
    }

    public function setPinCode(?string $pinCode): self
    {
        $this->pinCode = $pinCode;

        return $this;
    }

    public function getLandline(): ?string
    {
        return $this->landline;
    }

    public function setLandline(?string $landline): self
    {
        $this->landline = $landline;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function getParentOrganizationUnit(): ?self
    {
        return $this->parentOrganizationUnit;
    }

    public function setParentOrganizationUnit(?self $parentOrganizationUnit): self
    {
        $this->parentOrganizationUnit = $parentOrganizationUnit;

        return $this;
    }

    public function getOrganizationUnitType(): ?OrganizationType
    {
        return $this->organizationUnitType;
    }

    public function setOrganizationUnitType(?OrganizationType $organizationUnitType): self
    {
        $this->organizationUnitType = $organizationUnitType;

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

    public function getState(): ?State
    {
        return $this->state;
    }

    public function setState(?State $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getDistrict(): ?District
    {
        return $this->district;
    }

    public function setDistrict(?District $district): self
    {
        $this->district = $district;

        return $this;
    }

    public function getIsOffBoarders(): ?bool
    {
        return $this->isOffBoarders;
    }

    public function setIsOffBoarders(bool $isOffBoarders): self
    {
        $this->isOffBoarders = $isOffBoarders;

        return $this;
    }

}
