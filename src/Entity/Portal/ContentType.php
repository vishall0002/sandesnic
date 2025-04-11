<?php

namespace App\Entity\Portal;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="gim.content_type")
 * @ORM\Entity()
 */
class ContentType
{
    /**
     * @ORM\Id()
     * @ORM\Column(name="code",type="integer",unique=true, options={"comment":"Contenty Type code"})
     */
    private $code;
    
    /**
     * @ORM\Column(name="description",type="string", length=20,nullable=true, options={"comment":"Content type description"})
     */
    private $description;

    public function getCode(): ?int
    {
        return $this->code;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
