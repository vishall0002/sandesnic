<?php

namespace App\Entity\Masters;

use App\Entity\Portal\MetaData;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Traits\MasterTrait;

/**
 * MinistryCategory.
 * @Assert\GroupSequence({"MinistryCategory", "Length", "Regex"})
 * @ORM\Table(name="gim.masters_ministry_categories")
 * @ORM\Entity
 * @UniqueEntity("ministryCategory")
 * @ORM\HasLifecycleCallbacks
 */
class MinistryCategory
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
     * @Assert\NotBlank(message = "Ministry Code is required")
     * @Assert\Length(
     *      min = 3,
     *      max = 10,
     *      minMessage = "The Name  must be at least {{ limit }} characters long",
     *      maxMessage = "The Name cannot be longer than {{ limit }} characters long",
     *      groups={"Length"})
     *  @Assert\Regex(
     *      pattern     = "/^[A-Za-z,-_. 0-9]+$/i",
     *      message = "Enter a proper data, allowed only Characters, Digits, hiphen(-), us(_), dot(.) and spaces.",
     *      groups={"Regex"})
     * @ORM\Column(name="ministry_category", type="string", length=20)
     */

    private $ministryCategory;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMinistryCategory(): ?string
    {
        return $this->ministryCategory;
    }

    public function setMinistryCategory(string $ministryCategory): self
    {
        $this->ministryCategory = $ministryCategory;

        return $this;
    }


}
