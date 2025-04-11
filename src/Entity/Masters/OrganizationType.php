<?php

namespace App\Entity\Masters;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="gim.organization_type")
 * @ORM\Entity()
 */
class OrganizationType
{
    /**
     * @ORM\Column(name="code", type="string",  length=2)
     * @ORM\Id
     */
    private $id;

    /**
     * @ORM\Column(name="description", type="string", length=100)
     */
    private $organizationTypeName;
    
    public function __toString()
    {
        return $this->organizationTypeName;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getOrganizationTypeName(): ?string
    {
        return $this->organizationTypeName;
    }

    public function setOrganizationTypeName(string $organizationTypeName): self
    {
        $this->organizationTypeName = $organizationTypeName;

        return $this;
    }


}
