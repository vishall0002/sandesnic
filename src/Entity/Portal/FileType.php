<?php

namespace App\Entity\Portal;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="gim.file_type")
 * @ORM\Entity()
 */
class FileType
{
    /**
     * @ORM\Id()
     * @ORM\Column(name="code",type="string", length=10,unique=true)
     */
    private $code;
    
    /**
     * @ORM\Column(name="description",type="string", length=20,nullable=true)
     */
    private $description;

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
