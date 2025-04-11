<?php

namespace App\Entity\Lists;

use App\Entity\Masters\Country;
use App\Entity\Masters\ListSubscriberType;
use App\Entity\Masters\Ministry;
use App\Entity\Masters\PublisherRateLimiter;
use App\Entity\Masters\State;
use App\Entity\Portal\Employee;
use App\Entity\Portal\MetaData;
use App\Entity\Portal\OrganizationUnit;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * List_Publishers.
 *
 * @ORM\Table(name="gim.list_subscriber_countries")
 * @ORM\Entity
 */
class SubscriberCountry {

    /**
     * @var int
     *
     * @ORM\Column(name="list_subscriber_country_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="gu_id", type="guid")
     */
    private $guId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Masters\Country")
     * @ORM\JoinColumn(name="country_id", referencedColumnName="id",nullable=false)
     */
    private $subscriber;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Lists\SubscriberHead")
     * @ORM\JoinColumn(name="list_subscriber_head_id", referencedColumnName="list_subscriber_head_id",nullable=false)
     */
    private $subscriberHead;

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

    public function getSubscriber(): ?Country
    {
        return $this->subscriber;
    }

    public function setSubscriber(?Country $subscriber): self
    {
        $this->subscriber = $subscriber;

        return $this;
    }

    public function getSubscriberHead(): ?SubscriberHead
    {
        return $this->subscriberHead;
    }

    public function setSubscriberHead(?SubscriberHead $subscriberHead): self
    {
        $this->subscriberHead = $subscriberHead;

        return $this;
    }

   
}
