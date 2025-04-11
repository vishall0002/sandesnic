<?php

namespace App\Entity\Portal;

use Doctrine\ORM\Mapping as ORM;

/**
 * Agency
 *
 * @ORM\Table(name="gim.portal_one_time_links")
 * @ORM\Entity
 */
class OneTimeLink {

  /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(type="guid",  name="gu_id")
     */
    private $guId;

    /**
     * @ORM\Column(type="string",length=100, name="otl_for", nullable=true)
     */
    private $otlFor;

    /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Portal\User")
    * @ORM\JoinColumn(name="for_user_id", referencedColumnName="id")
    */
    private $forUser;

    /**
     * @ORM\Column(type="datetime", name="created_at")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="boolean", name="is_sent")
     */
    private $isSent;

    /**
     * @ORM\Column(type="boolean", name="is_accessed")
     */
    private $isAccessed;

    /**
     * @ORM\Column(type="datetime", name="accessed_at", nullable=true)
     */
    private $accessedAt;


    /**
     * @ORM\Column(type="string",length=32, name="accessed_ip", nullable=true)
     */
    private $accessedIP;

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

    public function getOtlFor(): ?string
    {
        return $this->otlFor;
    }

    public function setOtlFor(?string $otlFor): self
    {
        $this->otlFor = $otlFor;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getIsSent(): ?bool
    {
        return $this->isSent;
    }

    public function setIsSent(bool $isSent): self
    {
        $this->isSent = $isSent;

        return $this;
    }

    public function getIsAccessed(): ?bool
    {
        return $this->isAccessed;
    }

    public function setIsAccessed(bool $isAccessed): self
    {
        $this->isAccessed = $isAccessed;

        return $this;
    }

    public function getAccessedAt(): ?\DateTimeInterface
    {
        return $this->accessedAt;
    }

    public function setAccessedAt(?\DateTimeInterface $accessedAt): self
    {
        $this->accessedAt = $accessedAt;

        return $this;
    }

    public function getAccessedIP(): ?string
    {
        return $this->accessedIP;
    }

    public function setAccessedIP(?string $accessedIP): self
    {
        $this->accessedIP = $accessedIP;

        return $this;
    }

    public function getForUser(): ?User
    {
        return $this->forUser;
    }

    public function setForUser(?User $forUser): self
    {
        $this->forUser = $forUser;

        return $this;
    }

//    /**
//     * Constructor
//     */
//    public function __construct() {
//        $this->guId = md5(uniqid(php_uname('n')));
//        $this->createdAt = new \DateTime("now");
//    }

}
