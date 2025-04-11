<?php

namespace App\Entity\Masters;

use Doctrine\ORM\Mapping as ORM;
use App\Traits\MasterTrait;

/**
 * @ORM\Table(name="gim.masters_app_categories")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class AppCategory
{
    
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

     /**
     * @ORM\Column(name="gu_id", type="guid", nullable=true)
     */
    private $guId;

    /**
     * @var string
     *
     * @ORM\Column(name="cat_name", type="string", length=20)
     */
    private $categoryName;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategoryName(): ?string
    {
        return $this->categoryName;
    }

    public function setCategoryName(string $categoryName): self
    {
        $this->categoryName = $categoryName;

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
