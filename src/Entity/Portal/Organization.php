<?php

namespace App\Entity\Portal;

use App\Entity\Masters\OrganizationType;
use App\Entity\Masters\Ministry;
use App\Entity\Masters\Vhost;
use Doctrine\ORM\Mapping as ORM;
use App\Traits\MasterTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @Assert\GroupSequence({"Organization", "Length", "Regex"})

 * @ORM\Table(name="gim.organization")
 * @ORM\Entity()
 */
class Organization
{
    use MasterTrait;
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Masters\Ministry")
     * @ORM\JoinColumn(name="ministry_id", referencedColumnName="id", nullable=true)
     */
    private $ministry;
    
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Masters\Vhost")
     * @ORM\JoinColumn(name="vhost_id", referencedColumnName="id", nullable=true)
     */
    private $vhostId;

     /**
     * @ORM\Column(name="vhost", type="string", nullable=true)
     */
    private $vhost;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Masters\OrganizationType")
     * @ORM\JoinColumn(name="organization_type_id", referencedColumnName="code", nullable=false)
     */
    private $organizationType;

    /**
     * @Assert\NotBlank(message = "Organization Code is required")
     * @Assert\Length(
     *      min = 3,
     *      max = 10,
     *      minMessage = "The Name  must be at least {{ limit }} characters long",
     *      maxMessage = "The Name cannot be longer than {{ limit }} characters long",
     *      groups={"Length"})
     * @ORM\Column(name="organization_code", type="string", length=20)
     */
    private $organizationCode;

    /**
     * @Assert\NotBlank(message = "Organization Code is required")
     * @Assert\Length(
     *      min = 3,
     *      max = 50,
     *      minMessage = "The Name  must be at least {{ limit }} characters long",
     *      maxMessage = "The Name cannot be longer than {{ limit }} characters long",
     *      groups={"Length"})
     * @Assert\Regex(
     *      pattern     = "/^[A-Za-z,-_. 0-9]+$/i",
     *      message = "Enter a proper data, allowed only Characters, Digits, hiphen(-), us(_), dot(.) and spaces.",
     *      groups={"Regex"})
     * @ORM\Column(name="o_name", type="string", length=50)
     */
    private $organizationName;

    /**
     * @ORM\Column(name="is_o_visibility", type="boolean", nullable=true, options={"default"=false})
     */
    private $isOVisibility;

    /**
     * @ORM\Column(name="is_public_visibility", type="boolean", nullable=true, options={"default"=false})
     */
    private $isPublicVisibility;

    public function __toString()
    {
        return $this->organizationName;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrganizationCode(): ?string
    {
        return $this->organizationCode;
    }

    public function setOrganizationCode(string $organizationCode): self
    {
        $this->organizationCode = $organizationCode;

        return $this;
    }

    public function getOrganizationName(): ?string
    {
        return $this->organizationName;
    }

    public function setOrganizationName(string $organizationName): self
    {
        $this->organizationName = $organizationName;

        return $this;
    }

    public function getOrganizationType(): ?OrganizationType
    {
        return $this->organizationType;
    }

    public function setOrganizationType(?OrganizationType $organizationType): self
    {
        $this->organizationType = $organizationType;

        return $this;
    }

    public function getMinistry(): ?Ministry
    {
        return $this->ministry;
    }

    public function setMinistry(?Ministry $ministry): self
    {
        $this->ministry = $ministry;

        return $this;    }

    
    public function getOrganizationMinistry() {
        $var = substr($this->ministry, 0, 1);
        return $this->ministry . ' - ' . $this->organizationName;
    }

    public function getVhost(): ?string
    {
        return $this->vhost;
    }

    public function setVhost(string $vhost): self
    {
        $this->vhost = $vhost;

        return $this;
    }

    public function getVhostId(): ?Vhost
    {
        return $this->vhostId;
    }

    public function setVhostId(?Vhost $vhostId): self
    {
        $this->vhostId = $vhostId;

        return $this;
    }

    public function getIsOVisibility(): ?bool
    {
        return $this->isOVisibility;
    }

    public function setIsOVisibility(bool $isOVisibility): self
    {
        $this->isOVisibility = $isOVisibility;

        return $this;
    }

    public function getIsPublicVisibility(): ?bool
    {
        return $this->isPublicVisibility;
    }

    public function setIsPublicVisibility(bool $isPublicVisibility): self
    {
        $this->isPublicVisibility = $isPublicVisibility;

        return $this;
    }

}
