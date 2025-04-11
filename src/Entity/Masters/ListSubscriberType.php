<?php

namespace App\Entity\Masters;

use App\Entity\Portal\MetaData;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * ListCategory.
 *
 * @ORM\Table(name="gim.masters_list_subscriber_types")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ListSubscriberType
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="subscriber_type", type="string", length=20)
     */
    private $subscriberType;

    /**
     * @var string
     *
     * @ORM\Column(name="cf_code", type="string", length=20, nullable=true)
     */
    private $cfCode;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getSubscriberType(): ?string
    {
        return $this->subscriberType;
    }

    public function setSubscriberType(string $subscriberType): self
    {
        $this->subscriberType = $subscriberType;

        return $this;
    }

    public function getCfCode(): ?string
    {
        return $this->cfCode;
    }

    public function setCfCode(string $cfCode): self
    {
        $this->cfCode = $cfCode;

        return $this;
    }

}
