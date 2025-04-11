<?php

namespace App\Entity\Portal;

use Doctrine\ORM\Mapping as ORM;
use App\Traits\ApplicationTrait;
use App\Entity\Portal\OrganizationUnit;

/**
 * Roles
 *
 * @ORM\Table(name="gim.portal_import_employees")
 * @ORM\Entity
 */
class ImportEmployee {

    use ApplicationTrait;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="batch_code", type="string", length=50)
     */
    private $batchCode;

     /**
     * @var int
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(name="records_count", type="integer")
     */
    private $recordsCount;

    /**
     * @var string
     *
     * @ORM\Column(name="inserted_count", type="integer", nullable=true)
     */
    private $insertedCount;

    /**
     * @var string
     *
     * @ORM\Column(name="duplicates_count", type="integer", nullable=true)
     */
    private $duplicatesCount;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="upload_date", type="datetime", nullable=true)
     */
    private $uploadDate;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\OrganizationUnit")
     * @ORM\JoinColumn(name="ou_id", referencedColumnName="ou_id")
     */
    private $organizationUnit;

    /**
     * @ORM\Column(name="is_processed" , type="boolean", nullable=true, options={"default"=false})
     */
    private $isProcessed = 0;

    /**
     * @ORM\Column(name="is_scheduled" , type="boolean", nullable=true, options={"default"=false})
     */
    private $isScheduled = 0;

    /**
     * @ORM\Column(name="is_rejected" , type="boolean", nullable=true, options={"default"=false})
     */
    private $isRejected = 0;

    /**
     * @ORM\Column(name="is_processing" , type="boolean", nullable=true, options={"default"=false})
     */
    private $isProcessing = 0;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="processing_start_time", type="datetime", nullable=true)
     */
    private $processingStartTime;

    /**
     * @ORM\Column(name="is_finalised" , type="boolean", nullable=true, options={"default"=false})
     */
    private $isFinalised = 0;

    public function getId(): ?int {
        return $this->id;
    }

    public function getBatchCode(): ?string {
        return $this->batchCode;
    }

    public function setBatchCode(string $batchCode): self {
        $this->batchCode = $batchCode;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUploadDate(): ?\DateTimeInterface {
        return $this->uploadDate;
    }

    public function setUploadDate(\DateTimeInterface $uploadDate): self {
        $this->uploadDate = $uploadDate;

        return $this;
    }

    public function getIsProcessed(): ?bool {
        return $this->isProcessed;
    }

    public function setIsProcessed(?bool $isProcessed): self {
        $this->isProcessed = $isProcessed;

        return $this;
    }

    public function getIsFinalised(): ?bool {
        return $this->isFinalised;
    }

    public function setIsFinalised(?bool $isFinalised): self {
        $this->isFinalised = $isFinalised;

        return $this;
    }

    public function getIsScheduled(): ?bool {
        return $this->isScheduled;
    }

    public function setIsScheduled(?bool $isScheduled): self {
        $this->isScheduled = $isScheduled;

        return $this;
    }

    public function getIsRejected(): ?bool {
        return $this->isRejected;
    }

    public function setIsRejected(?bool $isRejected): self {
        $this->isRejected = $isRejected;

        return $this;
    }

    public function getOrganizationUnit(): ?OrganizationUnit {
        return $this->organizationUnit;
    }

    public function setOrganizationUnit(?OrganizationUnit $organizationUnit): self {
        $this->organizationUnit = $organizationUnit;

        return $this;
    }

    public function getRecordsCount(): ?int {
        return $this->recordsCount;
    }

    public function setRecordsCount(int $recordsCount): self {
        $this->recordsCount = $recordsCount;

        return $this;
    }

    public function getInsertedCount(): ?int {
        return $this->insertedCount;
    }

    public function setInsertedCount(?int $insertedCount): self {
        $this->insertedCount = $insertedCount;

        return $this;
    }

    public function getDuplicatesCount(): ?int {
        return $this->duplicatesCount;
    }

    public function setDuplicatesCount(?int $duplicatesCount): self {
        $this->duplicatesCount = $duplicatesCount;

        return $this;
    }

    public function getIsProcessing(): ?bool
    {
        return $this->isProcessing;
    }

    public function setIsProcessing(?bool $isProcessing): self
    {
        $this->isProcessing = $isProcessing;

        return $this;
    }

    public function getProcessingStartTime(): ?\DateTimeInterface
    {
        return $this->processingStartTime;
    }

    public function setProcessingStartTime(?\DateTimeInterface $processingStartTime): self
    {
        $this->processingStartTime = $processingStartTime;

        return $this;
    }

}
