<?php

namespace App\Entity\Portal;

use Doctrine\ORM\Mapping as ORM;

/**
 * Designation
 *
 * @ORM\Table(name="fmt.message_report")
 * @ORM\Entity()
 */
class FMT
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", options={"comment":"id"})
     * @ORM\Id
     */
    private $id;
    
    /**
     * @ORM\Column(name="tid", type="string", length=64, options={"comment":"Unique identification for a message trace"})
     */
    private $traceID;

    /**
     * @ORM\Column(name="trace_signature", type="string", length=500, options={"comment":"Signature of trace ID by originator (verified on submission)"})
     */
    private $traceSignature;
    
    /**
     * @ORM\Column(name="originator_public_key", type="string", length=200, options={"comment":"Public key used for verifiying the trace signature"})
     */
    private $originatorPublicKey;
    /**
     * @ORM\Column(name="message", type="string", length=5000, options={"comment":"The plain message"})
     */
    private $message;
    
    /**
     * @ORM\Column(name="hmac_key", type="string", length=32, options={"comment":"Used for trace ID generation"})
     */
    private $hmacKey;
    /**
     * @ORM\Column(name="sender_id", type="string", length=100, options={"comment":"Username of the sender"})
     */
    private $senderID;
    /**
     * @ORM\Column(name="receiver_id", type="string", length=100, options={"comment":"Username of the receiver (if message_type='C') or Group name (if message_type='G')"})
     */
    private $receiverID;
    /**
     * @ORM\Column(name="submitted_on", type="datetime", options={"comment":"Date of submission"})
     */
    private $submittedOn;
    /**
     * @ORM\Column(name="submitted_by", type="integer", options={"comment":"employee.id of the submitted user"})
     */
    private $submittedBy;
    /**
     * @ORM\Column(name="submission_reason_id", type="integer", options={"comment":"Refer fmt.masters_message_report_reasons"})
     */
    private $submittedReasonID;
    /**
     * @ORM\Column(name="message_type", type="string", length=1, options={"comment":"O-One-to-one, G-Group Chat"})
     */
    private $messageType;

    /**
     * @ORM\Column(name="gu_id", type="guid", nullable=true)
     */
    private $guId;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTraceID(): ?string
    {
        return $this->traceID;
    }

    public function setTraceID(string $traceID): self
    {
        $this->traceID = $traceID;

        return $this;
    }

    public function getTraceSignature(): ?string
    {
        return $this->traceSignature;
    }

    public function setTraceSignature(string $traceSignature): self
    {
        $this->traceSignature = $traceSignature;

        return $this;
    }

    public function getOriginatorPublicKey(): ?string
    {
        return $this->originatorPublicKey;
    }

    public function setOriginatorPublicKey(string $originatorPublicKey): self
    {
        $this->originatorPublicKey = $originatorPublicKey;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getHmacKey(): ?string
    {
        return $this->hmacKey;
    }

    public function setHmacKey(string $hmacKey): self
    {
        $this->hmacKey = $hmacKey;

        return $this;
    }

    public function getSenderID(): ?string
    {
        return $this->senderID;
    }

    public function setSenderID(string $senderID): self
    {
        $this->senderID = $senderID;

        return $this;
    }

    public function getReceiverID(): ?string
    {
        return $this->receiverID;
    }

    public function setReceiverID(string $receiverID): self
    {
        $this->receiverID = $receiverID;

        return $this;
    }

    public function getSubmittedOn(): ?\DateTimeInterface
    {
        return $this->submittedOn;
    }

    public function setSubmittedOn(\DateTimeInterface $submittedOn): self
    {
        $this->submittedOn = $submittedOn;

        return $this;
    }

    public function getSubmittedBy(): ?int
    {
        return $this->submittedBy;
    }

    public function setSubmittedBy(int $submittedBy): self
    {
        $this->submittedBy = $submittedBy;

        return $this;
    }

    public function getSubmittedReasonID(): ?int
    {
        return $this->submittedReasonID;
    }

    public function setSubmittedReasonID(int $submittedReasonID): self
    {
        $this->submittedReasonID = $submittedReasonID;

        return $this;
    }

    public function getMessageType(): ?string
    {
        return $this->messageType;
    }

    public function setMessageType(string $messageType): self
    {
        $this->messageType = $messageType;

        return $this;
    }

    public function getGuId(): ?string
    {
        return $this->guId;
    }

    public function setGuId(?string $guId): self
    {
        $this->guId = $guId;

        return $this;
    }


}
