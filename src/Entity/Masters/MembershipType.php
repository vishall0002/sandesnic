<?php

namespace App\Entity\Masters;

use App\Entity\Portal\MetaData;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * MembershipType.
 *
 * @ORM\Table(name="gim.masters_membership_types")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class MembershipType
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="membership_type", type="string", length=20)
     */
    private $membershipType;

    

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getMembershipType(): ?string
    {
        return $this->membershipType;
    }

    public function setMembershipType(string $membershipType): self
    {
        $this->membershipType = $membershipType;

        return $this;
    }

}
