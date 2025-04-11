<?php

namespace App\Entity\Lists;

use App\Entity\Masters\ListSubscriberType;
use App\Entity\Masters\Ministry;
use App\Entity\Masters\PublisherRateLimiter;
use App\Entity\Portal\Employee;
use App\Entity\Portal\MetaData;
use App\Entity\Portal\Organization;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * List_Publishers.
 *
 * @ORM\Table(name="gim.list_subscriber_orgs")
 * @ORM\Entity
 */
class SubscriberOrg {

    /**
     * @var int
     *
     * @ORM\Column(name="list_subscriber_org_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="gu_id", type="guid")
     */
    private $guId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\Organization")
     * @ORM\JoinColumn(name="org_id", referencedColumnName="id",nullable=false)
     */
    private $subscriber;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Lists\SubscriberHead")
     * @ORM\JoinColumn(name="list_subscriber_head_id", referencedColumnName="list_subscriber_head_id",nullable=false)
     */
    private $subscriberHead;

    public function getId(): ?int {
        return $this->id;
    }

    public function getSubscriberHead(): ?SubscriberHead {
        return $this->subscriberHead;
    }

    public function setSubscriberHead(?SubscriberHead $subscriberHead): self {
        $this->subscriberHead = $subscriberHead;

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

    public function getSubscriber(): ?Organization
    {
        return $this->subscriber;
    }

    public function setSubscriber(?Organization $subscriber): self
    {
        $this->subscriber = $subscriber;

        return $this;
    }

}
