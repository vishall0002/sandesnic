<?php

namespace App\Entity\Masters;

use App\Entity\Portal\MetaData;
use Doctrine\ORM\Mapping as ORM;
use App\Traits\MasterTrait;

/**
 * ListVisibility.
 *
 * @ORM\Table(name="gim.masters_list_visibilities")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ListVisibility
{    
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     */
    private $id;

     /**
     * @ORM\Column(name="gu_id", type="guid", nullable=true)
     */
    private $guId;

    /**
     * @var string
     *
     * @ORM\Column(name="visiblity_type", type="string", length=20)
     */
    private $visibilityType;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getVisibilityType(): ?string
    {
        return $this->visibilityType;
    }

    public function setVisibilityType(string $visibilityType): self
    {
        $this->visibilityType = $visibilityType;

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


}
