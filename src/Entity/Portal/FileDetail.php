<?php

namespace App\Entity\Portal;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="gim.file_detail", uniqueConstraints={
 *              @ORM\UniqueConstraint(name="file_details_file_hash_idx", columns={"file_hash"})
 * })
 * @ORM\Entity()
 */
class FileDetail
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\FileType")
     * @ORM\JoinColumn(name="file_type_code", referencedColumnName="code",nullable=false)
     */
    private $fileType;

    /**
     * @ORM\Column(name="file_hash",type="string",length=64, nullable=false, options={"comment":"File Hash (SHA256)"})
     */
    private $fileHash;

    /**
     * @ORM\Column(name="file_data",type="blob", options={"comment":"File Data"})
     */
    private $fileData;

    /**
     * @ORM\Column(name="thumbnail",type="blob", nullable=true, options={"comment":"Thumbnail for preview"})
     */
    private $thumbnail;
    
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\ContentType")
     * @ORM\JoinColumn(name="content_type_code", referencedColumnName="code",nullable=false)
     */
    private $contentTypeCode;
    

     /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_date", type="datetime", options={"default"="now()", "comment":"Created Date"})
     */
    private $createdDate;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFileHash(): ?string
    {
        return $this->fileHash;
    }

    public function setFileHash(?string $fileHash): self
    {
        $this->fileHash = $fileHash;

        return $this;
    }

    public function getFileData()
    {
        return $this->fileData;
    }

    public function setFileData($fileData): self
    {
        $this->fileData = $fileData;

        return $this;
    }

    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    public function setThumbnail($thumbnail): self
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }

    public function getCreatedDate(): ?\DateTimeInterface
    {
        return $this->createdDate;
    }

    public function setCreatedDate(\DateTimeInterface $createdDate): self
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    public function getFileType(): ?FileType
    {
        return $this->fileType;
    }

    public function setFileType(?FileType $fileType): self
    {
        $this->fileType = $fileType;

        return $this;
    }

    public function getContentTypeCode(): ?ContentType
    {
        return $this->contentTypeCode;
    }

    public function setContentTypeCode(?ContentType $contentTypeCode): self
    {
        $this->contentTypeCode = $contentTypeCode;

        return $this;
    }
}
