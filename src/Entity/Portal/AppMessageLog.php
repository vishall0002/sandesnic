<?php
namespace App\Entity\Portal;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Profile.
 *
 * @ORM\Table(name="gim.app_message_log")
 * @ORM\Entity
 */
class AppMessageLog{

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

       /**
   * @ORM\Column(name="app_id", type="integer" )
   */
  private $app;


    /**
     * @ORM\Column(type="string",length=20, name="req_type",nullable=true)
     */
    private $reqType;


       /**
     * @ORM\Column(name="req_body", type="text", nullable=true)
     */
    private $reqBody;


        /**
     * @ORM\Column(name="process_status" , type="boolean", options={"default"=false})
     */
    private $processStatus;


           /**
     * @ORM\Column(name="process_error", type="text", nullable=true)
     */
    private $processError;


        /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

       /**
     * @var \DateTime
     *
     * @ORM\Column(name="req_date", type="datetime", nullable=true)
     */
    private $reqDate;

        /**
     * @ORM\Column(name="attempt_counter", type="integer", nullable=true, options={"default":false})
     */
    private $attemptCounter;
    /**
     * @ORM\Column(name="guid", type="guid")
     */
    private $guId;

       /**
     * @var string
     *
     * @ORM\Column(name="req_ip", type="string",nullable=true, length=50)
     */
    private $reqIp;

        /**
     * @ORM\Column(name="dispatched_count", type="integer", options={"fixed" = true,"comment":"Dispatch"})
     */
    private $dispatchedCount;

     /**
     * @ORM\Column(name="delivered_count", type="integer", options={"fixed" = true,"comment":"Delivered count"})
     */
    private $deliveredCount;

    /**
     * @ORM\Column(name="read_count", type="integer", options={"fixed" = true,"comment":"Read count"})
     */
    private $readCount;

     /**
     * @var \DateTime
     *
     * @ORM\Column(name="dispatched_last_updated_on", type="datetime", nullable=true, options={"comment":"Stats last updated on"})
     */
    private $dispatchedLastUUpdatedUn;

       /**
     * @var \DateTime
     *
     * @ORM\Column(name="delivered_last_updated_on", type="datetime", nullable=true, options={"comment":"Stats last updated on"})
     */
    private $deliveredLastUpdatedOn;

       /**
     * @var \DateTime
     *
     * @ORM\Column(name="read_last_updated_on", type="datetime", nullable=true, options={"comment":"Stats last updated on"})
     */
    private $readLastUpdatedOn;

       /**
     * @ORM\Column(name="message_count", type="integer", nullable=true, options={"comment":"Total Message Count"})
     */
    private $messageCount;

    public function getId()
    {
        return $this->id;
    }

    public function getApp(): ?int
    {
        return $this->app;
    }

    public function setApp(int $app): self
    {
        $this->app = $app;

        return $this;
    }

    public function getReqType(): ?string
    {
        return $this->reqType;
    }

    public function setReqType(?string $reqType): self
    {
        $this->reqType = $reqType;

        return $this;
    }

    public function getReqBody(): ?string
    {
        return $this->reqBody;
    }

    public function setReqBody(?string $reqBody): self
    {
        $this->reqBody = $reqBody;

        return $this;
    }

    public function getProcessStatus(): ?bool
    {
        return $this->processStatus;
    }

    public function setProcessStatus(bool $processStatus): self
    {
        $this->processStatus = $processStatus;

        return $this;
    }

    public function getProcessError(): ?string
    {
        return $this->processError;
    }

    public function setProcessError(string $processError): self
    {
        $this->processError = $processError;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getReqDate(): ?\DateTimeInterface
    {
        return $this->reqDate;
    }

    public function setReqDate(?\DateTimeInterface $reqDate): self
    {
        $this->reqDate = $reqDate;

        return $this;
    }

    public function getEmployeeLevel(): ?int
    {
        return $this->employeeLevel;
    }

    public function setEmployeeLevel(?int $employeeLevel): self
    {
        $this->employeeLevel = $employeeLevel;

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

    public function getReqIp(): ?string
    {
        return $this->reqIp;
    }

    public function setReqIp(string $reqIp): self
    {
        $this->reqIp = $reqIp;

        return $this;
    }

    public function getDispatchedCount(): ?string
    {
        return $this->dispatchedCount;
    }

    public function setDispatchedCount(string $dispatchedCount): self
    {
        $this->dispatchedCount = $dispatchedCount;

        return $this;
    }

    public function getDeliveredCount(): ?string
    {
        return $this->deliveredCount;
    }

    public function setDeliveredCount(string $deliveredCount): self
    {
        $this->deliveredCount = $deliveredCount;

        return $this;
    }

    public function getReadCount(): ?string
    {
        return $this->readCount;
    }

    public function setReadCount(string $readCount): self
    {
        $this->readCount = $readCount;

        return $this;
    }

    public function getDispatchedLastUUpdatedUn(): ?\DateTimeInterface
    {
        return $this->dispatchedLastUUpdatedUn;
    }

    public function setDispatchedLastUUpdatedUn(?\DateTimeInterface $dispatchedLastUUpdatedUn): self
    {
        $this->dispatchedLastUUpdatedUn = $dispatchedLastUUpdatedUn;

        return $this;
    }

    public function getDeliveredLastUpdatedOn(): ?\DateTimeInterface
    {
        return $this->deliveredLastUpdatedOn;
    }

    public function setDeliveredLastUpdatedOn(?\DateTimeInterface $deliveredLastUpdatedOn): self
    {
        $this->deliveredLastUpdatedOn = $deliveredLastUpdatedOn;

        return $this;
    }

    public function getReadLastUpdatedOn(): ?\DateTimeInterface
    {
        return $this->readLastUpdatedOn;
    }

    public function setReadLastUpdatedOn(?\DateTimeInterface $readLastUpdatedOn): self
    {
        $this->readLastUpdatedOn = $readLastUpdatedOn;

        return $this;
    }

    public function getMessageCount(): ?int
    {
        return $this->messageCount;
    }

    public function setMessageCount(?int $messageCount): self
    {
        $this->messageCount = $messageCount;

        return $this;
    }

    public function getAttemptCounter(): ?int
    {
        return $this->attemptCounter;
    }

    public function setAttemptCounter(?int $attemptCounter): self
    {
        $this->attemptCounter = $attemptCounter;

        return $this;
    }




}