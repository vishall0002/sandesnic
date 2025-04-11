<?php

namespace App\Entity\Portal;

use Doctrine\ORM\Mapping as ORM;

/**
 * MetaData.
 *
 * @ORM\Table(name="gim.portal_metadata")
 * @ORM\Entity
 */
class MetaData
{
    /**
     * @var int
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="transaction_date_time", type="datetime", nullable=true)
     */
    private $transactionDateTime;

    /**
     * @var int
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\User")
     * @ORM\JoinColumn(name="transaction_user_id", referencedColumnName="id")
     */
    private $transactionUserId;

    /**
     * @var string
     *
     * @ORM\Column(name="transaction_remote_ip", type="string", length=15, nullable=true)
     */
    private $transactionRemoteIp;

    /**
     * @var string
     *
     * @ORM\Column(name="transaction_server_ip", type="string", length=15, nullable=true)
     */
    private $transactionServerIp;

    /**
     * @var bool
     *           I - Insert
     *           U - Update
     *           D - Delete
     *           OB - OffBoard
     *
     * @ORM\Column(name="transaction_type", type="string", length=2, nullable=true)
     */
    private $transactionType;

    public function __construct()
    {
        $this->transactionDateTime = new \DateTime('now');
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set transactionDateTime.
     *
     * @param \DateTime $transactionDateTime
     *
     * @return MetaData
     */
    public function setTransactionDateTime($transactionDateTime)
    {
        $this->transactionDateTime = $transactionDateTime;

        return $this;
    }

    /**
     * Get transactionDateTime.
     *
     * @return \DateTime
     */
    public function getTransactionDateTime()
    {
        return $this->transactionDateTime;
    }

    /**
     * Set transactionUserId.
     *
     * @param \App\Entity\Portal\User $transactionUserId
     *
     * @return MetaData
     */
    public function setTransactionUserId(\App\Entity\Portal\User $transactionUserId = null)
    {
        $this->transactionUserId = $transactionUserId;

        return $this;
    }

    /**
     * Get transactionUserId.
     *
     * @return \App\Entity\Portal\User
     */
    public function getTransactionUserId()
    {
        return $this->transactionUserId;
    }

    /**
     * Set transactionRemoteIp.
     *
     * @param string $transactionRemoteIp
     *
     * @return MetaData
     */
    public function setTransactionRemoteIp($transactionRemoteIp)
    {
        $this->transactionRemoteIp = $transactionRemoteIp;

        return $this;
    }

    /**
     * Get transactionRemoteIp.
     *
     * @return string
     */
    public function getTransactionRemoteIp()
    {
        return $this->transactionRemoteIp;
    }

    /**
     * Set transactionServerIp.
     *
     * @param string $transactionServerIp
     *
     * @return MetaData
     */
    public function setTransactionServerIp($transactionServerIp)
    {
        $this->transactionServerIp = $transactionServerIp;

        return $this;
    }

    /**
     * Get transactionServerIp.
     *
     * @return string
     */
    public function getTransactionServerIp()
    {
        return $this->transactionServerIp;
    }

    /**
     * Set transactionType.
     *
     * @param string $transactionType
     *
     * @return MetaData
     */
    public function setTransactionType($transactionType)
    {
        $this->transactionType = $transactionType;

        return $this;
    }

    /**
     * Get transactionType.
     *
     * @return string
     */
    public function getTransactionType()
    {
        return $this->transactionType;
    }
}
