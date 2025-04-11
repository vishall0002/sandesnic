<?php

namespace App\Entity\Masters;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="gim.masters_user_types")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class UserType
{
    /**
     * @var int
     *
     * @ORM\Column(name="user_type_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $userTypeId;

    /**
     * @var string
     *
     * @ORM\Column(name="user_type", type="string", length=30)
     */
    private $userType;
    
    /**
     * @ORM\Column(name="display_order", type="smallint")
     */
    private $displayOrder; 

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserType(): ?string
    {
        return $this->userType;
    }

    public function setUserType(string $userType): self
    {
        $this->userType = $userType;

        return $this;
    }

    public function getDisplayOrder(): ?int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): self
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    public function getUserTypeId(): ?int
    {
        return $this->userTypeId;
    }
      
}
