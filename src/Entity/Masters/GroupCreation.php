<?php

namespace App\Entity\Masters;

use Doctrine\ORM\Mapping as ORM;

/**
 * Gender.
 *
 * @ORM\Table(name="gim.masters_group_creations")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class GroupCreation
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
     * @ORM\Column(name="group_creation_code", type="string", length=10)
     */
    private $groupCreationCode;

    /**
     * @var string
     *
     * @ORM\Column(name="group_creation_name", type="string", length=100)
     */
    private $groupCreationName;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGroupCreationCode(): ?string
    {
        return $this->groupCreationCode;
    }

    public function setGroupCreationCode(string $groupCreationCode): self
    {
        $this->groupCreationCode = $groupCreationCode;

        return $this;
    }

    public function getGroupCreationName(): ?string
    {
        return $this->groupCreationName;
    }

    public function setGroupCreationName(string $groupCreationName): self
    {
        $this->groupCreationName = $groupCreationName;

        return $this;
    }

}