<?php

namespace App\Entity\Masters;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Entity
 * @ORM\Table(name="gim.masters_account_delete_reasons")
 */
class DeleteReason
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
     * @var string
     *
     * @ORM\Column(name="reason_description", type="string", length=50)
     */
    private $reasonDescription;

    /**
     * @ORM\Column(name="admin_reason" , type="boolean", nullable=false, options={"default"=false})
     */
    private $adminReason;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReasonDescription(): ?string
    {
        return $this->reasonDescription;
    }

    public function setReasonDescription(string $reasonDescription): self
    {
        $this->reasonDescription = $reasonDescription;

        return $this;
    }

    public function getAdminReason(): ?bool
    {
        return $this->adminReason;
    }

    public function setAdminReason(bool $adminReason): self
    {
        $this->adminReason = $adminReason;

        return $this;
    }

}
