<?php

namespace App\Entity\Masters;

use App\Entity\Portal\MetaData;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Traits\MasterTrait;

/**
 * Ministry.
 * @Assert\GroupSequence({"Ministry", "Length", "Regex"})
 * @ORM\Table(name="gim.masters_ministries")
 * @ORM\Entity
 * @UniqueEntity("ministryCode")
 * @ORM\HasLifecycleCallbacks
 */
class Ministry
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
     * @Assert\NotBlank(message = "Ministry Code is required")
     * @Assert\Length(
     *      min = 3,
     *      max = 10,
     *      minMessage = "The Name  must be at least {{ limit }} characters long",
     *      maxMessage = "The Name cannot be longer than {{ limit }} characters long",
     *      groups={"Length"})
     *  @Assert\Regex(
     *      pattern     = "/^[A-Za-z,-_. 0-9]+$/i",
     *      message = "Enter a proper data, allowed only Characters, Digits, hiphen(-), us(_), dot(.) and spaces.",
     *      groups={"Regex"})
     * @ORM\Column(name="ministry_code", type="string", length=50, unique=true)
     */

    private $ministryCode;

    /**
     * @Assert\NotBlank(message = "Organization Code is required")
     * @Assert\Length(
     *      min = 3,
     *      max = 50,
     *      minMessage = "The Name  must be at least {{ limit }} characters long",
     *      maxMessage = "The Name cannot be longer than {{ limit }} characters long",
     *      groups={"Length"})
     * @Assert\Regex(
     *      pattern     = "/^[A-Za-z,-_. 0-9]+$/i",
     *      message = "Enter a proper data, allowed only Characters, Digits, hiphen(-), us(_), dot(.) and spaces.",
     *      groups={"Regex"})
     * @ORM\Column(name="ministry_name", type="string", length=255)
     */
    private $ministryName;

    /**
     * @Assert\NotBlank(message = "Ministry NameLL is required")
     * @Assert\Length(
     *      min = 3,
     *      max = 50,
     *      minMessage = "The Name  must be at least {{ limit }} characters long",
     *      maxMessage = "The Name cannot be longer than {{ limit }} characters long",
     *      groups={"Length"})
     * @Assert\Regex(
     *      pattern     = "/^[A-Za-z,-_. 0-9]+$/i",
     *      message = "Enter a proper data, allowed only Characters, Digits, hiphen(-), us(_), dot(.) and spaces.",
     *      groups={"Regex"})
     * @ORM\Column(name="ministry_name_ll", type="string", length=255)
     */

    private $ministryNameLL;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Masters\MinistryCategory")
     * @ORM\JoinColumn(name="ministry_category_id", referencedColumnName="id",  columnDefinition="COMMENT  'Ministry Category Id'")
     */
    private $ministryCategoryId;

     /**
     * @ORM\Column(name="gu_id", type="guid")
     */
    private $guId;

    /**
     * @ORM\Column(name="insert_metadata_id", type="integer", nullable=true )
     */
    private $insertMetadata;

    /**
     * @ORM\Column(name="update_metadata_id", type="integer", nullable=true )
     */
    private $updateMetadata;

     /**
     * @var bool
     *
     * @ORM\Column(name="is_published", type="integer", nullable=true)
     */
    protected $isPublished;

    /**
     * @var int
     *
     * @ORM\Column(name="sort_order", type="integer", nullable=true)
     */
    protected $sortOrder;


    public function __toString()
    {
        return $this->ministryName;
    }
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMinistryCode(): ?string
    {
        return $this->ministryCode;
    }

    public function setMinistryCode(string $ministryCode): self
    {
        $this->ministryCode = $ministryCode;

        return $this;
    }

    public function getMinistryName(): ?string
    {
        return $this->ministryName;
    }

    public function setMinistryName(string $ministryName): self
    {
        $this->ministryName = $ministryName;

        return $this;
    }

    public function getMinistryNameLL(): ?string
    {
        return $this->ministryNameLL;
    }

    public function setMinistryNameLL(string $ministryNameLL): self
    {
        $this->ministryNameLL = $ministryNameLL;

        return $this;
    }

    public function getMinistryCategoryId(): ?MinistryCategory
    {
        return $this->ministryCategoryId;
    }

    public function setMinistryCategoryId(?MinistryCategory $ministryCategoryId): self
    {
        $this->ministryCategoryId = $ministryCategoryId;

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

    public function getInsertMetadata(): ?int
    {
        return $this->insertMetadata;
    }

    public function setInsertMetadata(?int $insertMetadata): self
    {
        $this->insertMetadata = $insertMetadata;

        return $this;
    }

    public function getUpdateMetadata(): ?int
    {
        return $this->updateMetadata;
    }

    public function setUpdateMetadata(?int $updateMetadata): self
    {
        $this->updateMetadata = $updateMetadata;

        return $this;
    }

    public function getIsPublished(): ?int
    {
        return $this->isPublished;
    }

    public function setIsPublished(?int $isPublished): self
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(?int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }


}
