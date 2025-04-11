<?php

namespace App\Entity\Portal;

use Doctrine\ORM\Mapping as ORM;

/**
 * Employee.
 *
 * @ORM\Table(name="gim.admin_requests")
 * @ORM\Entity
 */
class APIRequestStatus
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
     * @ORM\Column(name="req_type", type="string", length=20, nullable=true)
     */
    private $requestType;

    /**
     * @ORM\Column(name="req_body", type="text", nullable=true)
     */
    private $requestBody;

    /**
     * @ORM\Column(name="process_status", type="boolean", options={"default":false})
     */
    private $processStatus;

    /**
     * @ORM\Column(name="process_error", type="text", nullable=true)
     */
    private $processError; 

    /**
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\Column(name="attempt_counter", type="integer", options={"default":0})
     */
    private $attemptCounter;

    /**
     * @ORM\Column(name="req_date", type="datetime", nullable=true, options={"default"="now()"})
     */
    private $requestDate;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRequestType(): ?string
    {
        return $this->requestType;
    }

    public function setRequestType(string $requestType): self
    {
        $this->requestType = $requestType;

        return $this;
    }

    public function getRequestBody(): ?string
    {
        return $this->requestBody;
    }

    public function setRequestBody(string $requestBody): self
    {
        $this->requestBody = $requestBody;

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

    public function getAttemptCounter(): ?int
    {
        return $this->attemptCounter;
    }

    public function setAttemptCounter(int $attemptCounter): self
    {
        $this->attemptCounter = $attemptCounter;

        return $this;
    }

    public function getRequestDate(): ?\DateTimeInterface
    {
        return $this->requestDate;
    }

    public function setRequestDate(\DateTimeInterface $requestDate): self
    {
        $this->requestDate = $requestDate;

        return $this;
    }

    
}
