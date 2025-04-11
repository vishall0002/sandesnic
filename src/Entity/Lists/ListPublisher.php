<?php

namespace App\Entity\Lists;

use App\Entity\Masters\PublisherRateLimiter;
use App\Entity\Portal\Employee;
use App\Entity\Portal\MetaData;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * List_Publishers.
 *
 * @ORM\Table(name="gim.list_publishers")
 * @ORM\Entity
 */
class ListPublisher {

    /**
     * @var int
     *
     * @ORM\Column(name="list_publisher_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="gu_id", type="guid", nullable=true)
     */
    private $guId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Lists\BroadcastList")
     * @ORM\JoinColumn(name="list_id", referencedColumnName="list_id",nullable=false)
     */
    private $list;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\Employee")
     * @ORM\JoinColumn(name="emp_id", referencedColumnName="id",nullable=false)
     */
    private $employee;

    /**
   * @ORM\Column(name="insert_metadata_id", type="integer", nullable=true )
   */
    private $insertMetadata;

    /**
     * @ORM\Column(name="override_limit" , type="boolean", nullable=true, options={"default"=false})
     */
    private $overrideLimit;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Masters\PublisherRateLimiter")
     * @ORM\JoinColumn(name="rate_limiter_id", referencedColumnName="id", nullable=false)
     */
    private $rateLimiter;

    public function __construct() {
        $this->overrideLimit = false;
    }

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

    public function getInsertMetadata(): ?int
    {
        return $this->insertMetadata;
    }

    public function setInsertMetadata(?int $insertMetadata): self
    {
        $this->insertMetadata = $insertMetadata;

        return $this;
    }

    public function getOverrideLimit(): ?bool
    {
        return $this->overrideLimit;
    }

    public function setOverrideLimit(?bool $overrideLimit): self
    {
        $this->overrideLimit = $overrideLimit;

        return $this;
    }

    public function getList(): ?BroadcastList
    {
        return $this->list;
    }

    public function setList(?BroadcastList $list): self
    {
        $this->list = $list;

        return $this;
    }

    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    public function setEmployee(?Employee $employee): self
    {
        $this->employee = $employee;

        return $this;
    }

    public function getRateLimiter(): ?PublisherRateLimiter
    {
        return $this->rateLimiter;
    }

    public function setRateLimiter(?PublisherRateLimiter $rateLimiter): self
    {
        $this->rateLimiter = $rateLimiter;

        return $this;
    }


}
