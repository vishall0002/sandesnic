<?php

namespace App\Entity\Masters;

use App\Entity\Portal\MetaData;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Traits\MasterTrait;

/**
 * ListCategory.
 *
 * @ORM\Table(name="gim.masters_list_categories")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ListCategory
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
     * @var string
     *
     * @ORM\Column(name="category_name", type="string", length=30)
     */
    private $categoryName;

    public function getId(): ?string
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



}
