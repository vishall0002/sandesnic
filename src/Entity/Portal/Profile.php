<?php

namespace App\Entity\Portal;

use App\Entity\Masters\Ministry;
use App\Entity\Portal\OrganizationUnit;
use App\Entity\Portal\MetaData;
use Doctrine\ORM\Mapping as ORM;

/**
 * Profile
 *
 * @ORM\Table(name="gim.portal_user_profiles")
 * @ORM\Entity
 */
class Profile
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
     *
     * @ORM\Column(name="gu_id", type="guid")
     */
    private $guId;

    /**
     * @var integer;
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @var integer;
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\OrganizationUnit")
     * @ORM\JoinColumn(name="organization_unit_id", referencedColumnName="ou_id")
     */
    private $organizationUnit;

    /**
     * @var integer;
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", nullable=true)
     */
    private $organization;

    /**
     * @var integer;
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Masters\Ministry")
     * @ORM\JoinColumn(name="ministry_id", referencedColumnName="id", nullable=true)
     */
    private $ministry;
    
    /**
     * @var integer;
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\Roles")
     * @ORM\JoinColumn(name="role_id", referencedColumnName="id")
     */
    private $role;
    
    /**
     * @ORM\Column(type="datetime", name="from_date", nullable=true)
     */
    private $fromDate;
    
    /**
     * @ORM\Column(type="datetime", name="to_date" , nullable=true)
     */
    private $toDate;
    
    /**
     * @var integer
     * 1-additional charge
     *
     * @ORM\Column(name="is_additional", type="integer", nullable=true)
     */
    private $isAdditional;
    
    /**
     * @var integer
     * 1- additional charge is enabled
     *
     * @ORM\Column(name="is_enabled", type="integer", nullable=true)
     */
    private $isEnabled;
    
    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Portal\MetaData")
     * @ORM\JoinColumn(name="insert_metadata_id", referencedColumnName="id", nullable=true )
     */
    private $insertMetadata;
    
    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Portal\MetaData")
     * @ORM\JoinColumn(name="update_metadata_id", referencedColumnName="id", nullable=true )
     */
    private $updateMetadata;
    
    /**
     * @var integer
     * 1 - current user charge
     *
     * @ORM\Column(name="is_current", type="integer", nullable=true)
     */
    private $isCurrent;
    
    /**
     * @var integer
     * 1 - default user
     *
     * @ORM\Column(name="is_default", type="integer", nullable=true)
     */
    private $isDefault;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFromDate(): ?\DateTimeInterface
    {
        return $this->fromDate;
    }

    public function setFromDate(?\DateTimeInterface $fromDate): self
    {
        $this->fromDate = $fromDate;

        return $this;
    }

    public function getToDate(): ?\DateTimeInterface
    {
        return $this->toDate;
    }

    public function setToDate(?\DateTimeInterface $toDate): self
    {
        $this->toDate = $toDate;

        return $this;
    }

    public function getIsAdditional(): ?int
    {
        return $this->isAdditional;
    }

    public function setIsAdditional(?int $isAdditional): self
    {
        $this->isAdditional = $isAdditional;

        return $this;
    }

    public function getIsEnabled(): ?int
    {
        return $this->isEnabled;
    }

    public function setIsEnabled(?int $isEnabled): self
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    public function getIsCurrent(): ?int
    {
        return $this->isCurrent;
    }

    public function setIsCurrent(?int $isCurrent): self
    {
        $this->isCurrent = $isCurrent;

        return $this;
    }

    public function getIsDefault(): ?int
    {
        return $this->isDefault;
    }

    public function setIsDefault(?int $isDefault): self
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

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

    public function getRole(): ?Roles
    {
        return $this->role;
    }

    public function setRole(?Roles $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getInsertMetadata(): ?MetaData
    {
        return $this->insertMetadata;
    }

    public function setInsertMetadata(?MetaData $insertMetadata): self
    {
        $this->insertMetadata = $insertMetadata;

        return $this;
    }

    public function getUpdateMetadata(): ?MetaData
    {
        return $this->updateMetadata;
    }

    public function setUpdateMetadata(?MetaData $updateMetadata): self
    {
        $this->updateMetadata = $updateMetadata;

        return $this;
    }

    public function getGuId(): ?string
    {
        return $this->guId;
    }

    public function setGuId(string $guId): self
    {
        $this->guId = $guId;

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

    public function getMinistry(): ?Ministry
    {
        return $this->ministry;
    }

    public function setMinistry(?Ministry $ministry): self
    {
        $this->ministry = $ministry;

        return $this;
    }

}
