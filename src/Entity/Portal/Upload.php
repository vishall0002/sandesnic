<?php

namespace App\Entity\Portal;

use Doctrine\ORM\Mapping as ORM;

/**
 * Roles
 *
 * @ORM\Table(name="gim.portal_uploads")
 * @ORM\Entity
 */
class Upload
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
     *
     * @ORM\Column(name="gu_id", type="guid")
     */
    private $guId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="upload_date", type="datetime", nullable=true)
     */
    private $uploadDate;

    /**
     * @var string
     *
     * @ORM\Column(name="app_version", type="string", length=50)
     */
    private $appVersion;
   
    /**
     * @var string
     *
     * @ORM\Column(name="app_filename", type="string", length=255, nullable=true)
     */
    private $appFileName;

    /**
     * @var string
     *
     * @ORM\Column(name="app_manifestname", type="string", length=255, nullable=true)
     */
    private $appManifestName;

    /**
     * @var string
     *
     * @ORM\Column(name="app_type", type="string", length=20, nullable=true)
     */
    private $appType;
 
    /**
     * @var string
     *
     * @ORM\Column(name="app_build_no", type="string", length=50, nullable=true)
     */
    private $appBuildNo;

    /**
     * @var string
     *
     * @ORM\Column(name="app_version_no", type="string", length=50, nullable=true)
     */
    private $appVersionNo;

    /**
     * @var string
     *
     * @ORM\Column(name="app_release_notes", type="text")
     */
    private $appReleaseNotes;
   
    /**
     * @ORM\Column(name="is_current" , type="boolean", nullable=true, options={"default"=false})
     */
    private $isCurrent;

    /**
     * @ORM\Column(name="is_deleted" , type="boolean", nullable=true, options={"default"=false})
     */
    private $isDeleted;

    /**
     * @ORM\Column(name="is_beta" , type="boolean", nullable=true, options={"default"=false})
     */
    private $isBeta;
    
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

    public function getUploadDate(): ?\DateTimeInterface
    {
        return $this->uploadDate;
    }

    public function setUploadDate(\DateTimeInterface $uploadDate): self
    {
        $this->uploadDate = $uploadDate;

        return $this;
    }

    public function getAppVersion(): ?string
    {
        return $this->appVersion;
    }

    public function setAppVersion(string $appVersion): self
    {
        $this->appVersion = $appVersion;

        return $this;
    }

    public function getAppFileName(): ?string
    {
        return $this->appFileName;
    }

    public function setAppFileName(string $appFileName): self
    {
        $this->appFileName = $appFileName;

        return $this;
    }

    public function getAppManifestName(): ?string
    {
        return $this->appManifestName;
    }

    public function setAppManifestName(string $appManifestName): self
    {
        $this->appManifestName = $appManifestName;

        return $this;
    }

    public function getAppReleaseNotes(): ?string
    {
        return $this->appReleaseNotes;
    }

    public function setAppReleaseNotes(string $appReleaseNotes): self
    {
        $this->appReleaseNotes = $appReleaseNotes;

        return $this;
    }

    public function getIsCurrent(): ?bool
    {
        return $this->isCurrent;
    }

    public function setIsCurrent(?bool $isCurrent): self
    {
        $this->isCurrent = $isCurrent;

        return $this;
    }
   
    public function getIsBeta(): ?bool
    {
        return $this->isBeta;
    }

    public function setIsBeta(?bool $isBeta): self
    {
        $this->isBeta = $isBeta;

        return $this;
    }

    public function getIsDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(?bool $isDeleted): self
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    public function getAppType(): ?string
    {
        return $this->appType;
    }

    public function setAppType(string $appType): self
    {
        $this->appType = $appType;

        return $this;
    }

    public function getAppBuildNo(): ?string
    {
        return $this->appBuildNo;
    }

    public function setAppBuildNo(?string $appBuildNo): self
    {
        $this->appBuildNo = $appBuildNo;

        return $this;
    }

    public function getAppVersionNo(): ?string
    {
        return $this->appVersionNo;
    }

    public function setAppVersionNo(?string $appVersionNo): self
    {
        $this->appVersionNo = $appVersionNo;

        return $this;
    }

}
