<?php

namespace App\Traits;

/**
 * Description of ApplicationTrait
 *
 * @author amal
 */
use Doctrine\ORM\Mapping as ORM;

trait ApplicationTrait
{

    /**
     * @ORM\Column(name="gu_id", type="guid")
     */
    protected $guId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\MetaData")
     * @ORM\JoinColumn(name="insert_metadata_id", referencedColumnName="id", nullable=true )
     */
    protected $insertMetadata;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\MetaData")
     * @ORM\JoinColumn(name="update_metadata_id", referencedColumnName="id", nullable=true )
     */
    protected $updateMetadata;

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
