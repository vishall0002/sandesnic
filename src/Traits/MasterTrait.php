<?php

namespace App\Traits;

/**
 * Description of masterTraits
 *
 * @author amal
 */
use Doctrine\ORM\Mapping as ORM;

trait MasterTrait
{

    /**
     * @ORM\Column(name="gu_id", type="guid")
     */
    protected $guId;

    /**     
     * @ORM\Column(name="insert_metadata_id", type="integer",  nullable=true )
     */
    protected $insertMetadata;

    /**     
     * @ORM\Column(name="update_metadata_id", type="integer", nullable=true )
     */
    protected $updateMetadata;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_published", type="boolean", nullable=true)
     */
    protected $isPublished;

    /**
     * @var int
     *
     * @ORM\Column(name="sort_order", type="integer", nullable=true)
     */
    protected $sortOrder = 0;

    public function getIsPublished()
    {
        return $this->isPublished;
    }

    public function setIsPublished($isPublished)
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function getInsertMetadata()
    {
        return $this->insertMetadata;
    }

    public function setInsertMetadata($insertMetadata)
    {
        $this->insertMetadata = $insertMetadata;

        return $this;
    }

    public function getUpdateMetadata()
    {
        return $this->updateMetadata;
    }

    public function setUpdateMetadata($updateMetadata)
    {
        $this->updateMetadata = $updateMetadata;

        return $this;
    }

    public function getGuId()
    {
        return $this->guId;
    }

    public function setGuId($guId)
    {
        $this->guId = $guId;

        return $this;
    }
}
