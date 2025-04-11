<?php

namespace App\Entity\Lists;

use App\Entity\Masters\GroupCreation;
use App\Entity\Masters\GroupPurpose;
use App\Entity\Masters\GroupType;
use App\Entity\Masters\ListCategory;
use App\Entity\Masters\ListVisibility;
use App\Entity\Masters\MembershipType;
use App\Entity\Portal\Employee;
use App\Entity\Portal\MetaData;
use App\Entity\Portal\OrganizationUnit;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Group.
 *
 * @UniqueEntity("listName")
 * @UniqueEntity(
 *     fields={"guId"},
 *     errorPath="listName",
 *     message="This is an invalid submission, please make sure validity"
 * )
 * @ORM\Table(name="gim.lists")
 * })
 * @ORM\Entity
 */
class BroadcastList {

    /**
     * @var int
     *
     * @ORM\Column(name="list_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="gu_id", type="guid", unique=true)
     */
    private $guId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\OrganizationUnit")
     * @ORM\JoinColumn(name="parent_ou", referencedColumnName="ou_id", nullable=false)
     */
    private $organizationUnit;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Masters\ListCategory")
     * @ORM\JoinColumn(name="list_category_id", referencedColumnName="id", nullable=false)
     */
    private $listCategory;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Masters\ListVisibility")
     * @ORM\JoinColumn(name="visibility_id", referencedColumnName="id", nullable=false)
     */
    private $visibility;

    /**
     * @var string
     * @Assert\NotBlank(message = "List Name is required")
     * @Assert\Length(
     *      min = 3,
     *      max = 100,
     *      minMessage = "The Name  must be at least {{ limit }} characters long",
     *      maxMessage = "The Name cannot be longer than {{ limit }} characters long",
     *      groups={"Length"})
     * @Assert\Regex(
     *      pattern     = "/^[0-9a-z-]{3,99}+$/i",
     *      message = "Enter a proper name. Only lower case characters, hiphen(-) and numbers are allowed.",
     *      groups={"Regex"})
     * @ORM\Column(name="list_name", type="string", length=50, nullable=false )
     */
    private $listName;

    /**
     * @ORM\Column(name="insert_metadata_id", type="integer", nullable=false )
     */
    private $insertMetadata;

    /**
     * @ORM\Column(name="update_metadata_id", type="integer", nullable=true )
     */
    private $updateMetadata;

    /**
     * @ORM\Column(name="allow_unsubscribe" , type="boolean", nullable=false)
     */
    private $allowUnSubscribe;

    /**
     * @ORM\Column(name="active" , type="boolean", nullable=false, options={"default"=true})
     */
    private $isActive;

    /**
     * @ORM\Column(name="priority" ,  length=1, options={"fixed" = true, "default"="L"})
     */
    private $priority;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\Employee")
     * @ORM\JoinColumn(name="emp_id", referencedColumnName="id",nullable=true)
     */
    private $employee;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Masters\MembershipType")
     * @ORM\JoinColumn(name="membership_type_id", referencedColumnName="id", nullable=false)
     */
    private $membershipType;

    /**
     * @ORM\Column(name="description" , type="string", length=255, nullable=true, options={"comment"="Lit description"})
     */
    private $description;

    public function __construct() {
        $this->allowUnSubscribe = false;
        $this->isActive = false;
        $this->priority = 'L';
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getGuId(): ?string {
        return $this->guId;
    }

    public function setGuId(string $guId): self {
        $this->guId = $guId;

        return $this;
    }

    public function getListName(): ?string {
        return $this->listName;
    }

    public function setListName(string $listName): self {
        $this->listName = $listName;

        return $this;
    }

    public function getInsertMetadata(): ?int {
        return $this->insertMetadata;
    }

    public function setInsertMetadata(?int $insertMetadata): self {
        $this->insertMetadata = $insertMetadata;

        return $this;
    }

    public function getUpdateMetadata(): ?int {
        return $this->updateMetadata;
    }

    public function setUpdateMetadata(?int $updateMetadata): self {
        $this->updateMetadata = $updateMetadata;

        return $this;
    }

    public function getAllowUnSubscribe(): ?bool {
        return $this->allowUnSubscribe;
    }

    public function setAllowUnSubscribe(?bool $allowUnSubscribe): self {
        $this->allowUnSubscribe = $allowUnSubscribe;

        return $this;
    }

    public function getIsActive(): ?bool {
        return $this->isActive;
    }

    public function setIsActive(?bool $isActive): self {
        $this->isActive = $isActive;

        return $this;
    }

    public function getPriority(): ?string {
        return $this->priority;
    }

    public function setPriority(string $priority): self {
        $this->priority = $priority;

        return $this;
    }

    public function getOrganizationUnit(): ?OrganizationUnit {
        return $this->organizationUnit;
    }

    public function setOrganizationUnit(?OrganizationUnit $organizationUnit): self {
        $this->organizationUnit = $organizationUnit;

        return $this;
    }

    public function getListCategory(): ?ListCategory {
        return $this->listCategory;
    }

    public function setListCategory(?ListCategory $listCategory): self {
        $this->listCategory = $listCategory;

        return $this;
    }

    public function getVisibility(): ?ListVisibility {
        return $this->visibility;
    }

    public function setVisibility(?ListVisibility $visibility): self {
        $this->visibility = $visibility;

        return $this;
    }

    public function getEmployee(): ?Employee {
        return $this->employee;
    }

    public function setEmployee(?Employee $employee): self {
        $this->employee = $employee;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getMembershipType(): ?MembershipType
    {
        return $this->membershipType;
    }

    public function setMembershipType(?MembershipType $membershipType): self
    {
        $this->membershipType = $membershipType;

        return $this;
    }

}
