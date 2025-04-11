<?php

namespace App\Entity\Portal;

use App\Entity\Masters\GroupCreation;
use App\Entity\Masters\GroupPurpose;
use App\Entity\Masters\GroupType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Group.
 *
 * @Assert\GroupSequence({"Group", "Length", "Regex"})
 * @UniqueEntity("groupName")
 * @UniqueEntity(
 *     fields={"guId"},
 *     errorPath="groupName",
 *     message="This is an invalid submission, please make sure validity"
 * )
 * @ORM\Table(name="gim.""group""")
 * })
 * @ORM\Entity
 */
class Group
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
     * @ORM\Column(name="gu_id", type="guid", nullable=true)
     */
    private $guId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Masters\GroupType")
     * @ORM\JoinColumn(name="group_type_id", referencedColumnName="id")
     */
    private $groupType;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Masters\GroupPurpose")
     * @ORM\JoinColumn(name="group_purpose_id", referencedColumnName="id")
     */
    private $groupPurpose;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Masters\GroupCreation")
     * @ORM\JoinColumn(name="group_creation_id", referencedColumnName="id")
     */
    private $groupCreation;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\OrganizationUnit")
     * @ORM\JoinColumn(name="parent_ou", referencedColumnName="ou_id")
     */
    private $organizationUnit;

    /**
     * @var string
     * @Assert\NotBlank(message = "Group Name is required")
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
     * @ORM\Column(name="name", type="text", options={"comment":"Group name"})
     */
    private $groupName;

    /**
     * @var string
     * @Assert\Length(
     *      min = 3,
     *      max = 50,
     *      minMessage = "The Name  must be at least {{ limit }} characters long",
     *      maxMessage = "The Name cannot be longer than {{ limit }} characters long",
     *      groups={"Length"})
     * @Assert\Regex(
     *      pattern     = "/^[0-9A-Za-z \-._,&]{3,99}+$/i",
     *      message = "Enter a proper data, allowed only Characters, Digits, hiphen(-), us(_), dot(.) and spaces.",
     *      groups={"Regex"})
     * @ORM\Column(name="title", type="string", length=50, nullable=true, options={"comment":"Group title"})
     */
    private $groupTitle;

    /**
     * @var string
     * @Assert\Length(
     *      min = 3,
     *      max = 100,
     *      minMessage = "The Name  must be at least {{ limit }} characters long",
     *      maxMessage = "The Name cannot be longer than {{ limit }} characters long",
     *      groups={"Length"})
     * @Assert\Regex(
     *      pattern     = "/^[0-9A-Za-z \-._,&]{3,99}+$/i",
     *      message = "Enter a proper data, allowed only Characters, Digits, hiphen(-), us(_), dot(.) and spaces.",
     *      groups={"Regex"})
     * @ORM\Column(name="description", type="string", length=100, nullable=true, options={"comment":"Group description"})
     */
    private $groupDescription;

    /**
     * @ORM\Column(name="host", type="text", options={"comment"="Group Host"})
     */
    private $xmppHost;

    /**
     * @ORM\Column(name="insert_metadata_id", type="integer", nullable=true )
     */
    private $insertMetadata;

    /**
     * @ORM\Column(name="update_metadata_id", type="integer", nullable=true )
     */
    private $updateMetadata;

    /**
     * @ORM\Column(name="hidden" , type="boolean", nullable=true, options={"default"=false})
     */
    private $isHidden;

    /**
     * @ORM\Column(name="member_only" , type="boolean", nullable=true, options={"default"=true})
     */
    private $isMemberOnly;

    /**
     * @ORM\Column(name="moderated" , type="boolean", nullable=true, options={"default"=false})
     */
    private $isModerated;

    /**
     * @ORM\Column(name="password_protected" , type="boolean", nullable=true, options={"default"=false})
     */
    private $isPasswordProtected;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\FileDetail")
     * @ORM\JoinColumn(name="image", referencedColumnName="id", nullable=true)
     */
    private $photo;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\FileDetail")
     * @ORM\JoinColumn(name="cover_image", referencedColumnName="id", nullable=true)
     */
    private $coverImage;
    
     /**
     * @ORM\Column(name="e2ee", type="string", length=5, nullable=true, options={"comment"="Group E2EE "})
     */
    private $e2ee;
    

    public function __construct()
    {
        $this->isModerated = false;
        $this->isMemberOnly = true;
        $this->isPasswordProtected = false;
        $this->isHidden = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGuId(): ?string
    {
        return $this->guId;
    }

    public function setGuId(?string $guId): self
    {
        $this->guId = $guId;

        return $this;
    }

    public function getGroupName(): ?string
    {
        return $this->groupName;
    }

    public function setGroupName(string $groupName): self
    {
        $this->groupName = $groupName;

        return $this;
    }

    public function getGroupTitle(): ?string
    {
        return $this->groupTitle;
    }

    public function setGroupTitle(?string $groupTitle): self
    {
        $this->groupTitle = $groupTitle;

        return $this;
    }

    public function getGroupDescription(): ?string
    {
        return $this->groupDescription;
    }

    public function setGroupDescription(?string $groupDescription): self
    {
        $this->groupDescription = $groupDescription;

        return $this;
    }

    public function getXmppHost(): ?string
    {
        return $this->xmppHost;
    }

    public function setXmppHost(string $xmppHost): self
    {
        $this->xmppHost = $xmppHost;

        return $this;
    }

    public function getIsHidden(): ?bool
    {
        return $this->isHidden;
    }

    public function setIsHidden(?bool $isHidden): self
    {
        $this->isHidden = $isHidden;

        return $this;
    }

    public function getIsMemberOnly(): ?bool
    {
        return $this->isMemberOnly;
    }

    public function setIsMemberOnly(?bool $isMemberOnly): self
    {
        $this->isMemberOnly = $isMemberOnly;

        return $this;
    }

    public function getIsModerated(): ?bool
    {
        return $this->isModerated;
    }

    public function setIsModerated(?bool $isModerated): self
    {
        $this->isModerated = $isModerated;

        return $this;
    }

    public function getIsPasswordProtected(): ?bool
    {
        return $this->isPasswordProtected;
    }

    public function setIsPasswordProtected(?bool $isPasswordProtected): self
    {
        $this->isPasswordProtected = $isPasswordProtected;

        return $this;
    }

    public function getE2ee(): ?string
    {
        return $this->e2ee;
    }

    public function setE2ee(?string $e2ee): self
    {
        $this->e2ee = $e2ee;

        return $this;
    }

    public function getGroupType(): ?GroupType
    {
        return $this->groupType;
    }

    public function setGroupType(?GroupType $groupType): self
    {
        $this->groupType = $groupType;

        return $this;
    }

    public function getGroupPurpose(): ?GroupPurpose
    {
        return $this->groupPurpose;
    }

    public function setGroupPurpose(?GroupPurpose $groupPurpose): self
    {
        $this->groupPurpose = $groupPurpose;

        return $this;
    }

    public function getGroupCreation(): ?GroupCreation
    {
        return $this->groupCreation;
    }

    public function setGroupCreation(?GroupCreation $groupCreation): self
    {
        $this->groupCreation = $groupCreation;

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

    public function getPhoto(): ?FileDetail
    {
        return $this->photo;
    }

    public function setPhoto(?FileDetail $photo): self
    {
        $this->photo = $photo;

        return $this;
    }

    public function getCoverImage(): ?FileDetail
    {
        return $this->coverImage;
    }

    public function setCoverImage(?FileDetail $coverImage): self
    {
        $this->coverImage = $coverImage;

        return $this;
    }

    public function getInsertMetadata(): ?int
    {
        return $this->insertMetadata;
    }

    public function setInsertMetadata(?int $insertMetadata): self
    {
        $this->insertMetadata = $insertMetadata;

        return $this;
    }

    public function getUpdateMetadata(): ?int
    {
        return $this->updateMetadata;
    }

    public function setUpdateMetadata(?int $updateMetadata): self
    {
        $this->updateMetadata = $updateMetadata;

        return $this;
    }

   
}
