<?php

namespace App\Entity\Portal;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * DeviceSettings
 *
 * @ORM\Table(name="gim.device_settings")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class DeviceSettings
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @Assert\NotBlank(message = "Device OS is required")
     * @Assert\Type(type="alnum", message="Device OS should be alphanumeric.")
     * @ORM\Column(name="device_os", type="string", length=100)
     */
    private $deviceOS;

    /**
     * @var string
     * @Assert\NotBlank(message = "Device Make is required")
     * @Assert\Type(type="alnum", message="Code should be alphanumeric.")
     * @ORM\Column(name="device_make", type="string", length=100)
     */
    private $deviceMake;

    /**
     * @var string
     * @Assert\NotBlank(message = "Device Model is required")
     * @Assert\Type(type="alnum", message="Code should be alphanumeric.")
     * @ORM\Column(name="device_model", type="string", length=100)
     */
    private $deviceModel;
    /**
     * @var string
     * @Assert\NotBlank(message = "Auto Start Settings is required")
     * @Assert\Type(type="alnum", message="Auto Start Settings should be alphanumeric.")
     * @ORM\Column(name="settings_auto_start", type="string", length=100, nullable=true)
     */
    private $settingsAutoStart;
    /**
     * @var string
     * @Assert\NotBlank(message = "Battery Settings is required")
     * @Assert\Type(type="alnum", message="Battery Settings should be alphanumeric.")
     * @ORM\Column(name="settings_battery", type="string", length=100, nullable=true)
     */
    private $settingsBattery;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDeviceOS(): ?string
    {
        return $this->deviceOS;
    }

    public function setDeviceOS(string $deviceOS): self
    {
        $this->deviceOS = $deviceOS;

        return $this;
    }

    public function getDeviceMake(): ?string
    {
        return $this->deviceMake;
    }

    public function setDeviceMake(string $deviceMake): self
    {
        $this->deviceMake = $deviceMake;

        return $this;
    }

    public function getDeviceModel(): ?string
    {
        return $this->deviceModel;
    }

    public function setDeviceModel(string $deviceModel): self
    {
        $this->deviceModel = $deviceModel;

        return $this;
    }

    public function getSettingsAutoStart(): ?string
    {
        return $this->settingsAutoStart;
    }

    public function setSettingsAutoStart(string $settingsAutoStart): self
    {
        $this->settingsAutoStart = $settingsAutoStart;

        return $this;
    }

    public function getSettingsBattery(): ?string
    {
        return $this->settingsBattery;
    }

    public function setSettingsBattery(string $settingsBattery): self
    {
        $this->settingsBattery = $settingsBattery;

        return $this;
    }

}
