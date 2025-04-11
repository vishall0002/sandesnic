<?php

namespace App\Entity\Masters;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Portal\MetaData;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Traits\MasterTrait;

/**
 * Vhost.
 * @Assert\GroupSequence({"Vhost", "Length", "Regex"})
 * @ORM\Table(name="gim.vhost")
 * @ORM\Entity
  * @ORM\HasLifecycleCallbacks
 */

class Vhost
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
     * @Assert\NotBlank(message = "Vhost URL is required")
     * @Assert\Length(
     *      min = 3,
     *      max = 100,
     *      minMessage = "The Vhost URL  must be at least {{ limit }} characters long",
     *      maxMessage = "The Vhost URL cannot be longer than {{ limit }} characters long",
     *      groups={"Length"})
     *  @Assert\Regex(
     *      pattern     = "/^[A-Za-z,-_. 0-9]+$/i",
     *      message = "Enter a proper data, allowed only Characters, Digits, hiphen(-), us(_), dot(.) and spaces.",
     *      groups={"Regex"})
     * @ORM\Column(name="vhost_url", type="string", length=100)
     */
    private $vhostUrl;


      /**
     * @Assert\NotBlank(message = "Vhost Alias URL is required")
     * @Assert\Length(
     *      min = 3,
     *      max = 100,
     *      minMessage = "The Vhost Alias  must be at least {{ limit }} characters long",
     *      maxMessage = "The Vhost Alias cannot be longer than {{ limit }} characters long",
     *      groups={"Length"})
     *  @Assert\Regex(
     *      pattern     = "/^[A-Za-z,-_. 0-9]+$/i",
     *      message = "Enter a proper data, allowed only Characters, Digits, hiphen(-), us(_), dot(.) and spaces.",
     *      groups={"Regex"})
     * @ORM\Column(name="vhost_alias", type="string", length=100)
     */
    private $vhostAlias;
   
    public function __toString()
    {
        return $this->vhostAlias;
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVhostUrl(): ?string
    {
        return $this->vhostUrl;
    }

    public function setVhostUrl(string $vhostUrl): self
    {
        $this->vhostUrl = $vhostUrl;

        return $this;
    }

    public function getVhostAlias(): ?string
    {
        return $this->vhostAlias;
    }

    public function setVhostAlias(string $vhostAlias): self
    {
        $this->vhostAlias = $vhostAlias;

        return $this;
    }
}
