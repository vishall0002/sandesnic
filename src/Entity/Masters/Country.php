<?php

namespace App\Entity\Masters;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Portal\MetaData;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Country.
 * @Assert\GroupSequence({"country", "Length", "Regex"})
 * @ORM\Table(name="gim.country")
 * @ORM\Entity
  * @ORM\HasLifecycleCallbacks
 */

class Country
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
     * @ORM\Column(name="country_code", type="string", length=255, nullable=true)
     */
    private $countryCode;

    /**
     * @ORM\Column(name="mobile_country_code", type="string", length=255, nullable=true)
     */
    private $phoneCode;

    /**
     * @ORM\Column(name="country_name", type="string", length=255, nullable=true)
     */
    private $countryName;

     /**
     * @ORM\Column(name="display_order", type="integer", nullable=true)
     */
    private $displayOrder;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(?string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getPhoneCode(): ?string
    {
        return $this->phoneCode;
    }

    public function setPhoneCode(?string $phoneCode): self
    {
        $this->phoneCode = $phoneCode;

        return $this;
    }

    public function getCountryName(): ?string
    {
        return $this->countryName;
    }

    public function setCountryName(?string $countryName): self
    {
        $this->countryName = $countryName;

        return $this;
    }

    public function getDisplayOrder(): ?int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(?int $displayOrder): self
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }
   
}
