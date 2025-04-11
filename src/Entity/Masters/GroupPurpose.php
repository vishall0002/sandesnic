<?php

namespace App\Entity\Masters;

use Doctrine\ORM\Mapping as ORM;

/**
 * Gender.
 *
 * @ORM\Table(name="gim.masters_group_purposes")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class GroupPurpose
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="smallint", length=1)
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="group_purpose_code", type="string", length=10)
     */
    private $groupPurposeCode;

    /**
     * @var string
     *
     * @ORM\Column(name="group_purpose_name", type="string", length=100)
     */
    private $groupPurposeName;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGroupPurposeCode(): ?string
    {
        return $this->groupPurposeCode;
    }

    public function setGroupPurposeCode(string $groupPurposeCode): self
    {
        $this->groupPurposeCode = $groupPurposeCode;

        return $this;
    }

    public function getGroupPurposeName(): ?string
    {
        return $this->groupPurposeName;
    }

    public function setGroupPurposeName(string $groupPurposeName): self
    {
        $this->groupPurposeName = $groupPurposeName;

        return $this;
    }

}