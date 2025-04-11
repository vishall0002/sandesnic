<?php

namespace App\Entity\Masters;

use Doctrine\ORM\Mapping as ORM;

/**
 * Gender.
 *
 * @ORM\Table(name="gim.masters_offboard_reasons")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class OffBoardReason
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="smallint", length=1)
     * @ORM\Id
     */
    private $id;

    /**
     * @ORM\Column(name="gu_id", type="guid")
     */
    private $guId;

    /**
     * @var string
     *
     * @ORM\Column(name="offboard_reason_code", type="string", length=50)
     */
    private $offBoardReasonCode;

    /**
     * @var string
     *
     * @ORM\Column(name="offboard_reason_name", type="string", length=100)
     */
    private $offBoardReasonName;

    /**
     * @var string
     *
     * @ORM\Column(name="marker_icon", type="string", length=50)
     */
    private $markerIcon;

    /**
     * @ORM\Column(name="is_to_be_archived" , type="boolean", nullable=false, options={"default"="true"})
     */
    private $isToBeArchived;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getOffBoardReasonCode(): ?string
    {
        return $this->offBoardReasonCode;
    }

    public function setOffBoardReasonCode(string $offBoardReasonCode): self
    {
        $this->offBoardReasonCode = $offBoardReasonCode;

        return $this;
    }

    public function getOffBoardReasonName(): ?string
    {
        return $this->offBoardReasonName;
    }

    public function setOffBoardReasonName(string $offBoardReasonName): self
    {
        $this->offBoardReasonName = $offBoardReasonName;

        return $this;
    }

    public function getMarkerIcon(): ?string
    {
        return $this->markerIcon;
    }

    public function setMarkerIcon(string $markerIcon): self
    {
        $this->markerIcon = $markerIcon;

        return $this;
    }

    public function getIsToBeArchived(): ?bool
    {
        return $this->isToBeArchived;
    }

    public function setIsToBeArchived(bool $isToBeArchived): self
    {
        $this->isToBeArchived = $isToBeArchived;

        return $this;
    }
    


}