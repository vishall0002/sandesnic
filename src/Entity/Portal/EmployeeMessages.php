<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity\Portal;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
/**
 * Employee.
 *
 * @ORM\Table(name="gim.employee_messages")
 * @ORM\Entity
 */
class EmployeeMessages{

       /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"comment"="Id"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="gu_id", type="guid")
     */
    private $guId;

     /**
     * @var int
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @var array
     *
     * @ORM\Column(name="members", type="json_array")
     */
    private $members;
     /**
   * @ORM\Column(name="insert_metadata_id", type="integer", nullable=true )
   */
  private $insertMetadata;

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

    public function getMembers(): ?array
    {
        return $this->members;
    }

    public function setMembers(array $members): self
    {
        $this->members = $members;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getInsertMetadata(): ?int
    {
        return $this->insertMetadata;
    }

    public function setInsertMetadata(?int $insertMetadata): self
    {
        $this->insertMetadata = $insertMetadata;

        return $this;
    }



    
}