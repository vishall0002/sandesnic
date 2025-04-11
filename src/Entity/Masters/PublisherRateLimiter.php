<?php

namespace App\Entity\Masters;

use App\Entity\Portal\MetaData;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * ListCategory.
 *
 * @ORM\Table(name="gim.masters_publisher_rate_limiters")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class PublisherRateLimiter
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
     * @var string
     *
     * @ORM\Column(name="rate_limit_name", type="string") 
     */
    private $rateLimitName;

    /**
     * @var int
     *
     * @ORM\Column(name="rate", type="integer")     
     */
    private $rate;

    /**     
     * @ORM\Column(name="unit", length=1, options={"fixed" = true})   
     */
    private $unit;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRateLimitName(): ?string
    {
        return $this->rateLimitName;
    }

    public function setRateLimitName(string $rateLimitName): self
    {
        $this->rateLimitName = $rateLimitName;

        return $this;
    }

    public function getRate(): ?int
    {
        return $this->rate;
    }

    public function getUnit(): ?int
    {
        return $this->unit;
    }

    public function setRate(int $rate): self
    {
        $this->rate = $rate;

        return $this;
    }

    public function setUnit(string $unit): self
    {
        $this->unit = $unit;

        return $this;
    }



}
