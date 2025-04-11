<?php

namespace App\Entity\Masters;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Traits\MasterTrait;

/**
 * Gender.
 *
 * @ORM\Table(name="gim.masters_genders")
 * @ORM\Entity
 * @UniqueEntity("genderCode")
 * @ORM\HasLifecycleCallbacks
 */
class Gender
{
    use MasterTrait;
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="string", length=1)
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="gender", type="string", length=255)
     */
    private $gender;

    /**
     * @var string
     *
     * @ORM\Column(name="gender_ll", type="string", length=255)
     */
    private $genderLL;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getGenderLL(): ?string
    {
        return $this->genderLL;
    }

    public function setGenderLL(string $genderLL): self
    {
        $this->genderLL = $genderLL;

        return $this;
    }

}
