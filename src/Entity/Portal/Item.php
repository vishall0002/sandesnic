<?php

namespace App\Entity\Portal;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Item.
 *
 * @UniqueEntity("itemName")
 * @UniqueEntity(
 *     fields={"guId"},
 *     errorPath="itemName",
 *     message="This is an invalid submission, please make sure validity"
 * )
 * @ORM\Table(name="gim.portal_items")
 * })
 * @ORM\Entity
 */

class Item
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="gu_id", type="guid", nullable=false)
     */
    private $guId;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $itemName;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $itemText;

    /**
     * @ORM\Column(name="insert_metadata_id", type="integer", nullable=true )
     */
    private $insertMetadata;

    /**
     * @ORM\Column(name="update_metadata_id", type="integer", nullable=true )
     */
    private $updateMetadata;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $itemType;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getItemName(): ?string
    {
        return $this->itemName;
    }

    public function setItemName(string $itemName): self
    {
        $this->itemName = $itemName;

        return $this;
    }

    public function getItemText(): ?string
    {
        return $this->itemText;
    }

    public function setItemText(?string $itemText): self
    {
        $this->itemText = $itemText;

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

    public function getItemType(): ?string
    {
        return $this->itemType;
    }

    public function setItemType(string $itemType): self
    {
        $this->itemType = $itemType;

        return $this;
    }
}
