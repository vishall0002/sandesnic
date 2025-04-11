<?php

namespace App\Entity\Portal;

use Doctrine\ORM\Mapping as ORM;

/**
 * CUG
 *
 * @ORM\Table(name="gim.custom_reports")
 * @ORM\Entity
 */
class CustomReport {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="gu_id", type="guid", unique=true)
     */
    private $guId;

    /**
     * @ORM\Column(type="string", name="report_name")
     */
    private $reportName;

    /**
     * @ORM\Column(type="text", name="report_sql")
     */
    private $reportSql;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isPublished = 1;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isDownloadOnly = 0;

    
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

    public function getReportName(): ?string
    {
        return $this->reportName;
    }

    public function setReportName(string $reportName): self
    {
        $this->reportName = $reportName;

        return $this;
    }

    public function getReportSql(): ?string
    {
        return $this->reportSql;
    }

    public function setReportSql(string $reportSql): self
    {
        $this->reportSql = $reportSql;

        return $this;
    }

    public function getIsPublished(): ?bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(?bool $isPublished): self
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    public function getIsDownloadOnly(): ?bool
    {
        return $this->isDownloadOnly;
    }

    public function setIsDownloadOnly(?bool $isDownloadOnly): self
    {
        $this->isDownloadOnly = $isDownloadOnly;

        return $this;
    }

}
