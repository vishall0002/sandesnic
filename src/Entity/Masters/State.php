<?php

namespace App\Entity\Masters;

use App\Entity\Portal\MetaData;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Traits\MasterTrait;

/**
 * State.
 *
 * @ORM\Table(name="gim.masters_states")
 * @ORM\Entity
 * @UniqueEntity("stateCode")
 * @ORM\HasLifecycleCallbacks
 */
class State
{
    use MasterTrait;
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
     * @ORM\Column(name="state_code", type="string", length=10, nullable=true)
     */
    private $stateCode;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="string", length=255)
     */
    private $state;

    /**
     * @var string
     *
     * @ORM\Column(name="state_ll", type="string", length=255, nullable=true)
     */
    private $stateLL;

    /**  
     * @ORM\ManyToOne(targetEntity="App\Entity\Masters\Country")
     * @ORM\JoinColumn(name="country_id", referencedColumnName="id",nullable=true)
     */
    private $country;



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStateCode(): ?string
    {
        return $this->stateCode;
    }

    public function setStateCode(string $stateCode): self
    {
        $this->stateCode = $stateCode;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getStateLL(): ?string
    {
        return $this->stateLL;
    }

    public function setStateLL(string $stateLL): self
    {
        $this->stateLL = $stateLL;

        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): self
    {
        $this->country = $country;

        return $this;
    }



}
