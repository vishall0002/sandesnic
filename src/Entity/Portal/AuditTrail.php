<?php

namespace App\Entity\Portal;

use Doctrine\ORM\Mapping as ORM;

/**
 * Profile.
 *
 * @ORM\Table(name="report.zlogs_audit_trail")
 * @ORM\Entity
 */
class AuditTrail
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime", name="log_time")
     */
    private $logTime;

    /**
     * @ORM\Column(type="string",length=100, name="ip_address")
     */
    private $ipAddress;

    /**
     * @ORM\Column(type="string",length=100, name="session_id")
     */
    private $sessionId;

    /**
     * @ORM\Column(type="string",length=100, name="user_name")
     */
    private $userName;

    /**
     * @ORM\Column(type="string",length=100, name="bundle_name")
     */
    private $bundleName;

    /**
     * @ORM\Column(type="string",length=100, name="controller_name")
     */
    private $controllerName;

    /**
     * @ORM\Column(type="string",length=100, name="action_name")
     */
    private $actionName;

    /**
     * @ORM\Column(type="string",length=255, name="route")
     */
    private $route;

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
     * Set logTime.
     *
     * @param \DateTime $logTime
     *
     * @return AuditTrail
     */
    public function setLogTime($logTime)
    {
        $this->logTime = $logTime;

        return $this;
    }

    /**
     * Get logTime.
     *
     * @return \DateTime
     */
    public function getLogTime()
    {
        return $this->logTime;
    }

    /**
     * Set ipAddress.
     *
     * @param string $ipAddress
     *
     * @return AuditTrail
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Get ipAddress.
     *
     * @return string
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * Set sessionId.
     *
     * @param string $sessionId
     *
     * @return AuditTrail
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId.
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set userName.
     *
     * @param string $userName
     *
     * @return AuditTrail
     */
    public function setUserName($userName)
    {
        $this->userName = $userName;

        return $this;
    }

    /**
     * Get userName.
     *
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * Set bundleName.
     *
     * @param string $bundleName
     *
     * @return AuditTrail
     */
    public function setBundleName($bundleName)
    {
        $this->bundleName = $bundleName;

        return $this;
    }

    /**
     * Get bundleName.
     *
     * @return string
     */
    public function getBundleName()
    {
        return $this->bundleName;
    }

    /**
     * Set controllerName.
     *
     * @param string $controllerName
     *
     * @return AuditTrail
     */
    public function setControllerName($controllerName)
    {
        $this->controllerName = $controllerName;

        return $this;
    }

    /**
     * Get controllerName.
     *
     * @return string
     */
    public function getControllerName()
    {
        return $this->controllerName;
    }

    /**
     * Set actionName.
     *
     * @param string $actionName
     *
     * @return AuditTrail
     */
    public function setActionName($actionName)
    {
        $this->actionName = $actionName;

        return $this;
    }

    /**
     * Get actionName.
     *
     * @return string
     */
    public function getActionName()
    {
        return $this->actionName;
    }

    /**
     * Set route.
     *
     * @param string $route
     *
     * @return AuditTrail
     */
    public function setRoute($route)
    {
        $this->route = $route;

        return $this;
    }

    /**
     * Get route.
     *
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }
}
