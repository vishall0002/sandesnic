<?php

namespace App\Entity\Lists;

use App\Entity\Masters\ListSubscriberType;
use App\Entity\Masters\PublisherRateLimiter;
use App\Entity\Portal\Employee;
use App\Entity\Portal\MetaData;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * List_Publishers.
 *
 * @ORM\Table(name="gim.list_subscriber_heads")
 * @ORM\Entity
 */
class SubscriberHead {

    /**
     * @var int
     *
     * @ORM\Column(name="list_subscriber_head_id", type="integer")
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
     * @ORM\ManyToOne(targetEntity="App\Entity\Masters\ListSubscriberType")
     * @ORM\JoinColumn(name="subscriber_type_id", referencedColumnName="id",nullable=false)
     */
    private $subscriberType;

    public function getId(): ?int {
        return $this->id;
    }

    public function getList(): ?BroadcastList {
        return $this->list;
    }

    public function setList(?BroadcastList $list): self {
        $this->list = $list;

        return $this;
    }

    public function getSubscriberType(): ?ListSubscriberType {
        return $this->subscriberType;
    }

    public function setSubscriberType(?ListSubscriberType $subscriberType): self {
        $this->subscriberType = $subscriberType;

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

}
