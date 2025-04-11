<?php

namespace App\Entity\Masters;

use Doctrine\ORM\Mapping as ORM;

/**
 * Gender.
 *
 * @ORM\Table(name="gim.masters_group_types")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class GroupType
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
     * @ORM\Column(name="group_type_code", type="string", length=10)
     */
    private $groupTypeCode;
    
    /**
     * @var string
     *
     * @ORM\Column(name="group_type_name", type="string", length=100)
     */
    private $groupTypeName;

    /**
     * @var string
     *
     * @ORM\Column(name="group_suffix", type="string", length=10, nullable=true)
     */
    private $groupSuffix;
    
    /**
     * @var string
     *
     * @ORM\Column(name="is_group_readonly", type="boolean", nullable=true)     
     */
    private $isGroupReadonly;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGroupTypeCode(): ?string
    {
        return $this->groupTypeCode;
    }

    public function setGroupTypeCode(string $groupTypeCode): self
    {
        $this->groupTypeCode = $groupTypeCode;

        return $this;
    }

    public function getGroupTypeName(): ?string
    {
        return $this->groupTypeName;
    }

    public function setGroupTypeName(string $groupTypeName): self
    {
        $this->groupTypeName = $groupTypeName;

        return $this;
    }

    public function getGroupSuffix(): ?string
    {
        return $this->groupSuffix;
    }

    public function setGroupSuffix(string $groupSuffix): self
    {
        $this->groupSuffix = $groupSuffix;

        return $this;
    }

    public function getIsGroupReadonly(): ?bool
    {
        return $this->isGroupReadonly;
    }

    public function setIsGroupReadonly(bool $isGroupReadonly): self
    {
        $this->isGroupReadonly = $isGroupReadonly;

        return $this;
    }

}
