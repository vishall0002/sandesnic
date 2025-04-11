<?php

namespace App\Entity\Masters;

use App\Entity\Portal\MetaData;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Traits\MasterTrait;

/**
 * District.
 *
 * @ORM\Table(name="gim.masters_districts")
 * @ORM\Entity
 * @UniqueEntity("districtCode")
 * @ORM\HasLifecycleCallbacks
 */
class District
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
     * @ORM\Column(name="district_code", type="string", length=10, nullable=true)
     */
    private $districtCode;

    /**
     * @var string
     *
     * @ORM\Column(name="district", type="string", length=255)
     */
    private $district;
    
     /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Masters\State")
     * @ORM\JoinColumn(name="state_id", referencedColumnName="id",nullable=true)
     */
    private $state;

    /**
     * @var string
     *
     * @ORM\Column(name="district_ll", type="string", length=255, nullable=true)
     */
    private $districtLL;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDistrictCode(): ?string
    {
        return $this->districtCode;
    }

    public function setDistrictCode(string $districtCode): self
    {
        $this->districtCode = $districtCode;

        return $this;
    }

    public function getDistrict(): ?string
    {
        return $this->district;
    }

    public function setDistrict(string $district): self
    {
        $this->district = $district;

        return $this;
    }

    public function getDistrictLL(): ?string
    {
        return $this->districtLL;
    }

    public function setDistrictLL(string $districtLL): self
    {
        $this->districtLL = $districtLL;

        return $this;
    }

    public function getState(): ?State
    {
        return $this->state;
    }

    public function setState(?State $state): self
    {
        $this->state = $state;

        return $this;
    }

}
