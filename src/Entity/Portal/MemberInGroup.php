<?php

namespace App\Entity\Portal;

use App\Entity\Masters\GroupAffiliation;
use App\Entity\Masters\GroupRole;
use Doctrine\ORM\Mapping as ORM;

/**
 * Employee.
 *
 * @ORM\Table(name="gim.group_member")
 * @ORM\Entity
 */
class MemberInGroup
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
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\Employee")
     * @ORM\JoinColumn(name="employee_id", referencedColumnName="id")
     */
    private $employee;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\Group")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id")
     */
    private $group;

    /**
     * @var string
     * @ORM\Column(name="group_name", type="text")
     */
    private $groupName;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Masters\GroupAffiliation")
     * @ORM\JoinColumn(name="affiliation", referencedColumnName="code")
     */
    private $groupAffiliation;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Masters\GroupRole")
     * @ORM\JoinColumn(name="role", referencedColumnName="code")
     */
    private $groupRole;

    /**
     * @ORM\ManyToOne(targetEntity="MetaData")
     * @ORM\JoinColumn(name="insert_metadata_id", referencedColumnName="id", nullable=true )
     */
    private $insertMetadata;

     /**
     * @ORM\ManyToOne(targetEntity="MetaData")
     * @ORM\JoinColumn(name="update_metadata_id", referencedColumnName="id", nullable=true )
     */
    private $updateMetadata;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGroupName(): ?string
    {
        return $this->groupName;
    }

    public function setGroupName(string $groupName): self
    {
        $this->groupName = $groupName;

        return $this;
    }

    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    public function setEmployee(?Employee $employee): self
    {
        $this->employee = $employee;

        return $this;
    }

    public function getGroup(): ?Group
    {
        return $this->group;
    }

    public function setGroup(?Group $group): self
    {
        $this->group = $group;

        return $this;
    }

    public function getGroupAffiliation(): ?GroupAffiliation
    {
        return $this->groupAffiliation;
    }

    public function setGroupAffiliation(?GroupAffiliation $groupAffiliation): self
    {
        $this->groupAffiliation = $groupAffiliation;

        return $this;
    }

    public function getGroupRole(): ?GroupRole
    {
        return $this->groupRole;
    }

    public function setGroupRole(?GroupRole $groupRole): self
    {
        $this->groupRole = $groupRole;

        return $this;
    }

    public function getInsertMetadata(): ?MetaData
    {
        return $this->insertMetadata;
    }

    public function setInsertMetadata(?MetaData $insertMetadata): self
    {
        $this->insertMetadata = $insertMetadata;

        return $this;
    }

    public function getUpdateMetadata(): ?MetaData
    {
        return $this->updateMetadata;
    }

    public function setUpdateMetadata(?MetaData $updateMetadata): self
    {
        $this->updateMetadata = $updateMetadata;

        return $this;
    }
}
