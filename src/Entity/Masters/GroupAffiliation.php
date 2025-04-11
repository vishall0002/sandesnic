<?php

namespace App\Entity\Masters;

use Doctrine\ORM\Mapping as ORM;

/**
 * Gender.
 *
 * @ORM\Table(name="gim.group_affiliation")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class GroupAffiliation
{
    /**
     * @var int
     *
     * @ORM\Column(name="code", type="smallint", length=1)
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255)
     */
    private $description;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
