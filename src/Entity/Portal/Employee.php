<?php

namespace App\Entity\Portal;

use App\Entity\Masters\Country;
use App\Entity\Masters\District;
use App\Entity\Masters\Gender;
use App\Entity\Masters\OffBoardReason;
use App\Entity\Masters\State;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Employee.
 *
 * @Assert\GroupSequence({"Employee", "Length", "Regex"})
 * @UniqueEntity(
 *     fields={"guId"},
 *     errorPath="employeeName",
 *     message="This is an invalid submission, please make sure validity"
 * )
 * @ORM\Table(name="gim.employee", indexes={@ORM\Index(name="employee_mobile_no_unique", columns={"mobile_no"})})
 * @ORM\Entity
 */
 

class Employee
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
     * @ORM\Column(name="gu_id", type="guid")
     */
    private $guId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\OrganizationUnit")
     * @ORM\JoinColumn(name="ou_id", referencedColumnName="ou_id")
     */
    private $organizationUnit;

    /**
     * @var int
     *
     * @ORM\OneToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Designation")
     * @ORM\JoinColumn(name="designation_code", referencedColumnName="id",  columnDefinition="COMMENT  'Designation id'")
     */
    private $designation;

    /**
     * @var string
     * @Assert\NotBlank(message = "Employee Code is required")
     * @Assert\Length(
     *      min = 1,
     *      max = 10,
     *      minMessage = "The Employee Code  must be at least {{ limit }} characters long",
     *      maxMessage = "The Employee Code cannot be longer than {{ limit }} characters long",
     *      groups={"Length"})
     * @ORM\Column(name="employee_code", type="string", length=10, nullable=true)
     */
    private $employeeCode;

    /**
     * @var string
     * @Assert\NotBlank(message = "Employee Name is required")
     * @Assert\Length(
     *      min = 3,
     *      max = 100,
     *      minMessage = "The Name  must be at least {{ limit }} characters long",
     *      maxMessage = "The Name cannot be longer than {{ limit }} characters long",
     *      groups={"Length"})
     * @Assert\Regex(
     *      pattern     = "/^[A-Za-z\. ']{3,99}+$/i",
     *      message = "Enter a proper name. Only characters, period(.), space and apostrophe(') allowed.",
     *      groups={"Regex"})
     * @ORM\Column(name="name", type="string", length=50, options={"comment":"Employee name"})
     */
    private $employeeName;

    /**
     * @var string
     * @Assert\Length(
     *      min = 5,
     *      max = 15,
     *      minMessage = "The Mobile Number  must be at least {{ limit }} characters long",
     *      maxMessage = "The Mobile Number cannot be longer than {{ limit }} characters long",
     *      groups={"Length"})
     * @Assert\Regex(
     *     pattern     = "/^(0)?[0-9]{5,15}$/i",
     *      groups={"Regex"})
     * @ORM\Column(name="mobile_no", type="string", nullable=true, length=12, options={"comment":"Mobile number"})
     */
    private $mobileNumber;
    // REFERENCE: Please see the mail from Deepak Mittal on 16-11-2021 and subsequent FNA from MPA
    /**
     * @var string
     * @Assert\Length(
     *      min = 7,
     *      max = 115,
     *      minMessage = "The email  must be at least {{ limit }} characters long",
     *      maxMessage = "The email cannot be longer than {{ limit }} characters long",
     *      groups={"Length"})
     * @Assert\Regex(
     *     pattern     = "/^[A-Za-z0-9._%+-]{2,100}@[A-Za-z0-9.-]{2,20}\.[A-Za-z]{2,10}$/i",
     *      message = "Valid E-Mail Address is required",
     *      groups={"Regex"})
     * @ORM\Column(name="email", type="string", nullable=true, length=254, options={"comment":"Email"})
     */
    private $emailAddress;

    /**
     * @var string
     * @Assert\Length(
     *      min = 0,
     *      max = 115,
     *      minMessage = "The email  must be at least {{ limit }} characters long",
     *      maxMessage = "The email cannot be longer than {{ limit }} characters long",
     *      groups={"Length"})
     * @Assert\Regex(
     *     pattern     = "/^[A-Za-z0-9._%+-]{2,100}@[A-Za-z0-9.-]{2,20}\.[A-Za-z]{2,10}$/i",
     *      message = "Valid E-Mail Address is required",
     *      groups={"Regex"})
     * @ORM\Column(name="alternate_email", type="string", length=254, nullable=true, options={"comment":"Alternate email for 2F "})
     */
    private $alternateEmailAddress;

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

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Masters\Gender")
     * @ORM\JoinColumn(name="gender", referencedColumnName="id")
     */
    private $gender;

    /**
     * @var \Date
     *
     * @ORM\Column(name="dob", type="date",nullable=true)
     */
    private $dob;

    /**
     * @var \Date
     *
     * @ORM\Column(name="dosa", type="date",nullable=true)
     */
    private $dosa;

    /**
     * @var string
     *
     * @ORM\Column(name="jid", type="string", length=254,  options={"comment":"Jabber Id"})
     */
    private $jabberId;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=255,  options={"comment":"Jabber Username"})
     */
    private $jabberName;

    /**
     * @ORM\Column(name="active" , type="string", length=1,  options={"default"="Y", "comment":"Account Active flag"})
     */
    private $isActive;

    /**
     * @ORM\Column(name="registered" , type="string", length=1, options={"default"="N", "comment":"Registration flag"})
     */
    private $isRegistered;

    /**
     * @ORM\Column(name="is_retired" , type="boolean", nullable=true, options={"default"=false})
     */
    private $isRetired;

    /**
     * @ORM\Column(name="is_deceased" , type="boolean", nullable=true, options={"default"=false})
     */
    private $isDeceased;

    /**
     * @ORM\Column(name="is_ou_manager" , type="boolean", nullable=true, options={"default"=false})
     */
    private $isOUManager;

    /**
     * @ORM\Column(name="is_ou_admin" , type="boolean", nullable=true, options={"default"=false})
     */
    private $isOUAdmin;

    /**
     * @ORM\Column(name="is_nodal_officer" , type="boolean", nullable=true, options={"default"=false})
     */
    private $isNodalOfficer;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Masters\OffBoardReason")
     * @ORM\JoinColumn(name="offboard_reason_id", referencedColumnName="id")
     */
    private $offBoardReason;

    /**
     * @ORM\ManyToOne(targetEntity="MetaData")
     * @ORM\JoinColumn(name="offboard_metadata_id", referencedColumnName="id", nullable=true )
     */
    private $offBoardMetadata;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="registered_date", type="datetime", nullable=true, options={"comment":"Date of registration"})
     */
    private $registeredDate;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\FileDetail")
     * @ORM\JoinColumn(name="photo", referencedColumnName="id", nullable=true)
     */
    private $photo;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal\FileDetail")
     * @ORM\JoinColumn(name="cover_image", referencedColumnName="id", nullable=true)
     */
    private $coverImage;

    /**
     * @ORM\ManyToOne(targetEntity="EmployeeLevel")
     * @ORM\JoinColumn(name="employee_level_id", referencedColumnName="id", nullable=true)
     */
    private $employeeLevelID;

    /**
     * @ORM\Column(name="level", type="integer", nullable=true, options={"comment":"Employee Level"})
     */
    private $employeeLevel;

    /**
     * @ORM\Column(name="auth_privilege", type="integer", nullable=true, options={"comment":"Employee default privileges"})
     */
    private $authPrivilege;

    /**
     * @ORM\Column(name="host", type="text", nullable=true, options={"comment":"Host Name"})
     */
    private $host;

    /**
     * @ORM\Column(name="backup_key",  length=32, nullable=true, options={"fixed" = true})
     */
    private $backupKey;

    /**
     * @ORM\Column(name="tfa" , type="boolean",  options={"default"=true, "comment":"Two Factor Authentication flag"})
     */
    private $tfa;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Masters\Country")
     * @ORM\JoinColumn(name="country_id", referencedColumnName="id", nullable=true)
     */
    private $country;

    /**
     * @ORM\Column(name="mobile_country_code", type="string", nullable=true,  length=10, options={"comment":"Mobile Country Code"})
     */
    private $phoneCode;

    /**
     * @ORM\Column(name="e2ee", type="string",  length=5, options={"default":"v1", "comment":"E2EE Activation Flag"})
     */
    private $e2ee;
    /**
     * @ORM\Column(name="location", nullable=true,  length=2, options={"fixed" = true, "comment"="Location (Country Code) or IN/OI (India/Outside India)"})
     */
    private $location;

    /**
     * @ORM\Column(name="app_type", type="string", nullable=false,  length=1, options={"default" : "L", "fixed" = true, "comment":"L-LIte, P-Premium"})
     */
    private $appType;

    /**
     * @ORM\Column(name="registration_mode", type="string", length=5, options={"default":"O", "comment":"Mode of Registration"})
     */
    private $registrationMode;

    /**
     * @ORM\Column(name="account_status", length=1, options={"default":"U", "comment":"Account Status","fixed" = true})
     */
    private $accountStatus;

    /**
     * @ORM\Column(name="user_type_id", type="integer", options={"default" : 5})
     */
    private $userType;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Masters\State")
     * @ORM\JoinColumn(name="state_id", referencedColumnName="id")
     */
    private $state;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Masters\District")
     * @ORM\JoinColumn(name="district_id", referencedColumnName="id")
     */
    private $district;
    
    /**
     * @ORM\Column(name="account_type" , length=1, options={"default"="U","fixed" = true, "comment":"Account Type: U-User, A-App, L-List"})
     */
    private $accountType;

    /**
     * @ORM\Column(name="ip_phone" , length=10, type="string", nullable=true)
     */
    private $ipPhone;

    /**
     * @ORM\Column(name="email_alias" , length=254, type="string", nullable=true, options={"comment":"Email Alias"})
     */
    private $emailAlias;

    /**
     * @ORM\Column(name="onboarding_remarks" , length=255, type="string", nullable=true)
     */
    private $onboardingRemarks;

    /**
     * @ORM\Column(name="onboard_process_metadata_id", type="integer", nullable=true)
     */
    private $onboardProcessMetadataId;

    /**
     * @ORM\Column(name="onboarding_request_remarks" , length=255, type="string", nullable=true)
     */
    private $onboardingRequestRemarks;

    /**
     * @ORM\Column(name="uid_token" , length=72, type="string", nullable=true)
     */
    private $uidToken;

    public function __construct()
    {
        $this->isActive = 'Y';
        $this->isRegistered = 'N';
        $this->e2ee = 'v2';
        $this->isRetired = false;
        $this->isDeceased = false;
        $this->isOUAdmin = false;
        $this->isOUManager = false;
        $this->tfa = false;
        $this->host = 'gimkerala.nic.in';
        $this->appType = 'P';
        $this->registrationMode = 'O';
        $this->accountStatus = 'V';
        $this->accountType = 'U';
        $this->userType = 5;
    }

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

    public function getEmployeeCode(): ?string
    {
        return $this->employeeCode;
    }

    public function setEmployeeCode(string $employeeCode): self
    {
        $this->employeeCode = $employeeCode;

        return $this;
    }

    public function getEmployeeName(): ?string
    {
        return $this->employeeName;
    }

    public function setEmployeeName(string $employeeName): self
    {
        $this->employeeName = $employeeName;

        return $this;
    }

    public function getMobileNumber(): ?string
    {
        return $this->mobileNumber;
    }

    public function setMobileNumber(?string $mobileNumber): self
    {
        $this->mobileNumber = $mobileNumber;

        return $this;
    }

    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    public function setEmailAddress(?string $emailAddress): self
    {
        $this->emailAddress = strtolower($emailAddress);

        return $this;
    }

    public function getDob(): ?\DateTimeInterface
    {
        return $this->dob;
    }

    public function setDob(?\DateTimeInterface $dob): self
    {
        $this->dob = $dob;

        return $this;
    }

    public function getJabberId(): ?string
    {
        return $this->jabberId;
    }

    public function setJabberId(?string $jabberId): self
    {
        $this->jabberId = strtolower($jabberId);

        return $this;
    }

    public function getJabberName(): ?string
    {
        return $this->jabberName;
    }

    public function setJabberName(?string $jabberName): self
    {
        $this->jabberName = strtolower($jabberName);

        return $this;
    }
    
    public function getIsActive(): ?string
    {
        return $this->isActive;
    }

    public function setIsActive(?string $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getIsRegistered(): ?string
    {
        return $this->isRegistered;
    }

    public function setIsRegistered(?string $isRegistered): self
    {
        $this->isRegistered = $isRegistered;

        return $this;
    }

    public function getRegisteredDate(): ?\DateTimeInterface
    {
        return $this->registeredDate;
    }

    public function setRegisteredDate(?\DateTimeInterface $registeredDate): self
    {
        $this->registeredDate = $registeredDate;

        return $this;
    }

    public function getEmployeeLevel(): ?int
    {
        return $this->employeeLevel;
    }

    public function setEmployeeLevel(?int $employeeLevel): self
    {
        $this->employeeLevel = $employeeLevel;

        return $this;
    }
   
    public function getAuthPrivilege(): ?int
    {
        return $this->authPrivilege;
    }

    public function setAuthPrivilege(?int $authPrivilege): self
    {
        $this->authPrivilege = $authPrivilege;

        return $this;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function setHost(?string $host): self
    {
        $this->host = strtolower($host);

        return $this;
    }

    public function getOrganizationUnit(): ?OrganizationUnit
    {
        return $this->organizationUnit;
    }

    public function setOrganizationUnit(?OrganizationUnit $organizationUnit): self
    {
        $this->organizationUnit = $organizationUnit;

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

    public function getDesignation(): ?Designation
    {
        return $this->designation;
    }

    public function setDesignation(?Designation $designation): self
    {
        $this->designation = $designation;

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

    public function getGender(): ?Gender
    {
        return $this->gender;
    }

    public function setGender(?Gender $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getPhoto(): ?FileDetail
    {
        return $this->photo;
    }

    public function setPhoto(?FileDetail $photo): self
    {
        $this->photo = $photo;

        return $this;
    }

    public function getCoverImage(): ?FileDetail
    {
        return $this->coverImage;
    }

    public function setCoverImage(?FileDetail $coverImage): self
    {
        $this->coverImage = $coverImage;

        return $this;
    }

    public function getEmployeeLevelID(): ?EmployeeLevel
    {
        return $this->employeeLevelID;
    }

    public function setEmployeeLevelID(?EmployeeLevel $employeeLevelID): self
    {
        $this->employeeLevelID = $employeeLevelID;

        return $this;
    }

    public function getBackupKey(): ?string
    {
        return $this->backupKey;
    }

    public function setBackupKey(?string $backupKey): self
    {
        $this->backupKey = $backupKey;

        return $this;
    }

    public function getIsRetired(): ?bool
    {
        return $this->isRetired;
    }

    public function setIsRetired(?bool $isRetired): self
    {
        $this->isRetired = $isRetired;

        return $this;
    }

    public function getIsDeceased(): ?bool
    {
        return $this->isDeceased;
    }

    public function setIsDeceased(?bool $isDeceased): self
    {
        $this->isDeceased = $isDeceased;

        return $this;
    }

    public function getIsOUManager(): ?bool
    {
        return $this->isOUManager;
    }

    public function setIsOUManager(?bool $isOUManager): self
    {
        $this->isOUManager = $isOUManager;

        return $this;
    }

    public function getIsOUAdmin(): ?bool
    {
        return $this->isOUAdmin;
    }

    public function setIsOUAdmin(?bool $isOUAdmin): self
    {
        $this->isOUAdmin = $isOUAdmin;

        return $this;
    }

    public function getOffBoardReason(): ?OffBoardReason
    {
        return $this->offBoardReason;
    }

    public function setOffBoardReason(?OffBoardReason $offBoardReason): self
    {
        $this->offBoardReason = $offBoardReason;

        return $this;
    }

    public function getOffBoardMetadata(): ?MetaData
    {
        return $this->offBoardMetadata;
    }

    public function setOffBoardMetadata(?MetaData $offBoardMetadata): self
    {
        $this->offBoardMetadata = $offBoardMetadata;

        return $this;
    }

    public function getIsNodalOfficer(): ?bool
    {
        return $this->isNodalOfficer;
    }

    public function setIsNodalOfficer(?bool $isNodalOfficer): self
    {
        $this->isNodalOfficer = $isNodalOfficer;

        return $this;
    }

    public function getAlternateEmailAddress(): ?string
    {
        return $this->alternateEmailAddress;
    }

    public function setAlternateEmailAddress(?string $alternateEmailAddress): self
    {
        $this->alternateEmailAddress = strtolower($alternateEmailAddress);

        return $this;
    }

    public function getTfa(): ?bool
    {
        return $this->tfa;
    }

    public function setTfa(?bool $tfa): self
    {
        $this->tfa = $tfa;

        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getPhoneCode(): ?string
    {
        return $this->phoneCode;
    }

    public function setPhoneCode(string $phoneCode): self
    {
        $this->phoneCode = $phoneCode;

        return $this;
    }

    public function getE2ee(): ?string
    {
        return $this->e2ee;
    }

    public function setE2ee(?string $e2ee): self
    {
        $this->e2ee = $e2ee;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getAppType(): ?string
    {
        return $this->appType;
    }

    public function setAppType(?string $appType): self
    {
        $this->appType = $appType;

        return $this;
    }

    public function setRegistrationMode(?string $registrationMode): self
    {
        $this->registrationMode = $registrationMode;

        return $this;
    }

    public function getRegistrationMode(): ?string
    {
        return $this->registrationMode;
    }

    public function getAccountStatus(): ?string
    {
        return $this->accountStatus;
    }

    public function setAccountStatus(?string $accountStatus): self
    {
        $this->accountStatus = $accountStatus;

        return $this;
    }

    public function getState(): ?State
    {
        return $this->state;
    }

    public function setState(?State $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getDistrict(): ?District
    {
        return $this->district;
    }

    public function setDistrict(?District $district): self
    {
        $this->district = $district;

        return $this;
    }

    public function getUserType(): ?int
    {
        return $this->userType;
    }

    public function setUserType(?int $userType): self
    {
        $this->userType = $userType;

        return $this;
    }

    public function getAccountType(): ?string
    {
        return $this->accountType;
    }

    public function setAccountType(string $accountType): self
    {
        $this->accountType = $accountType;

        return $this;
    }

    public function getIpPhone(): ?string
    {
        return $this->ipPhone;
    }

    public function setIpPhone(?string $ipPhone): self
    {
        $this->ipPhone = $ipPhone;

        return $this;
    }

    public function getEmailAlias(): ?string
    {
        return $this->emailAlias;
    }

    public function setEmailAlias(?string $emailAlias): self
    {
        $this->emailAlias = $emailAlias;

        return $this;
    }

    public function getOnboardingRemarks(): ?string
    {
        return $this->onboardingRemarks;
    }

    public function setOnboardingRemarks(?string $onboardingRemarks): self
    {
        $this->onboardingRemarks = $onboardingRemarks;

        return $this;
    }

    public function getOnboardProcessMetadataId(): ?int
    {
        return $this->onboardProcessMetadataId;
    }

    public function setOnboardProcessMetadataId(?int $onboardProcessMetadataId): self
    {
        $this->onboardProcessMetadataId = $onboardProcessMetadataId;

        return $this;
    }

    public function getOnboardingRequestRemarks(): ?string
    {
        return $this->onboardingRequestRemarks;
    }

    public function setOnboardingRequestRemarks(?string $onboardingRequestRemarks): self
    {
        $this->onboardingRequestRemarks = $onboardingRequestRemarks;

        return $this;
    }
    
    public function getDosa(): ?\DateTimeInterface
    {
        return $this->dosa;
    }
    
    public function setDosa(?\DateTimeInterface $dosa): self
    {
        $this->dosa = $dosa;
        
        return $this;
    }

    public function getUidToken(): ?string
    {
        return $this->uidToken;
    }

    public function setUidToken(?string $uidToken): self
    {
        $this->uidToken = $uidToken;

        return $this;
    }

}
