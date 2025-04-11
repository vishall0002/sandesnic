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
// use Symfony\Component\Security\Core\User\EquatableInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table(name="gim.portal_users")
 *
 */
class User implements UserInterface, \Serializable
// class User implements UserInterface, EquatableInterface, \Serializable
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $fullName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true, length=180)
     * @Assert\NotBlank()
     * @Assert\Length(min=2, max=50)
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true, length=180)
     * @Assert\Email()
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @var array
     *
     * @ORM\Column(type="json", options={"comment":"(DC2Type:array)"})
     */
    private $roles = [];

     /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $salt;

    /**
     * @var int
     *
     * @ORM\Column(name="gu_id", type="guid", unique=true)
     */
    private $guId;

    /**
     * @ORM\Column(name="attempted",type="integer",length=1, options={ "default"=0 })
     */
    protected $attempted = 0;

    /**
     * @ORM\Column(type="smallint",name="is_logged", options={ "default"=0 })
     */
    protected $isLogged = 0;

    /**
     * @ORM\Column(type="smallint",name="is_suspended", options={ "default"=0 })
     */
    protected $isSuspended = 0;

    /**
     * @ORM\Column(type="datetime",name="attempted_at", nullable=true)
     */
    protected $attemptedAt;

    /**
     * @ORM\Column(type="boolean",name="is_fcp", options={ "default"=0 })
     */
    protected $isFcp = 0;

    /**
     * @ORM\Column(type="boolean",name="is_email_verified", options={ "default"=0 })
     */
    protected $isEmailVerified = 0;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_mobile_verified", type="boolean")
     */
    private $isMobileVerified = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_notification_opted", options={ "default"=0 },type="integer")
     */
    private $isNotificationOpted = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_ldap", type="boolean")
     */
    private $isLDAP = true;

    /**
     * @var int
     *
     * @ORM\Column(name="mobile_number", type="string", length=11, nullable=true)
     */
    private $mobileNumber;

    /**
     * @ORM\Column(type="smallint",name="is_beta_user", options={ "default"=0 })
     */
    protected $isBetaUser = 0;

    /**
     * @ORM\Column(type="boolean",name="enabled", options={ "default"=true })
     */
    protected $enabled = 1;
     /**
     * @var string
     *
     * @ORM\Column(name="session_id", type="string", length=40, nullable=true)
     */
    private $sessionId;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString()
    {
        return $this->username;
    }

    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * Returns the roles or permissions granted to the user for security.
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        // guarantees that a user always has at least one role for security
        if (empty($roles)) {
            $roles[] = 'ROLE_USER';
        }

        return array_unique($roles);
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function hasRole($role)
    {
        return in_array(strtoupper($role), $this->getRoles(), true);
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * {@inheritdoc}
     */
    public function getSalt(): ?string
    {
        return $this->salt;
    }

    public function setSalt(string $salt): void
    {
        $this->salt = $salt;
    }

    /**
     * Removes sensitive data from the user.
     *
     * {@inheritdoc}
     */
    public function eraseCredentials(): void
    {
        // if you had a plainPassword property, you'd nullify it here
        // $this->plainPassword = null;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize(): string
    {
        // add $this->salt too if you don't use Bcrypt or Argon2i
        return serialize([$this->id, $this->username, $this->password, $this->salt]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized): void
    {
        // add $this->salt too if you don't use Bcrypt or Argon2i
        [$this->id, $this->username, $this->password, $this->salt] = unserialize($serialized, ['allowed_classes' => false]);
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

    public function getAttempted(): ?int
    {
        return $this->attempted;
    }

    public function setAttempted(int $attempted): self
    {
        $this->attempted = $attempted;

        return $this;
    }

    public function getIsLogged(): ?int
    {
        return $this->isLogged;
    }

    public function setIsLogged(int $isLogged): self
    {
        $this->isLogged = $isLogged;

        return $this;
    }

    public function getIsSuspended(): ?int
    {
        return $this->isSuspended;
    }

    public function setIsSuspended(int $isSuspended): self
    {
        $this->isSuspended = $isSuspended;

        return $this;
    }

    public function getAttemptedAt(): ?\DateTimeInterface
    {
        return $this->attemptedAt;
    }

    public function setAttemptedAt(?\DateTimeInterface $attemptedAt): self
    {
        $this->attemptedAt = $attemptedAt;

        return $this;
    }

    public function getIsFcp(): ?bool
    {
        return $this->isFcp;
    }

    public function setIsFcp(bool $isFcp): self
    {
        $this->isFcp = $isFcp;

        return $this;
    }

    public function getIsEmailVerified(): ?bool
    {
        return $this->isEmailVerified;
    }

    public function setIsEmailVerified(bool $isEmailVerified): self
    {
        $this->isEmailVerified = $isEmailVerified;

        return $this;
    }

    public function getIsMobileVerified(): ?bool
    {
        return $this->isMobileVerified;
    }

    public function setIsMobileVerified(bool $isMobileVerified): self
    {
        $this->isMobileVerified = $isMobileVerified;

        return $this;
    }

    public function getIsNotificationOpted(): ?int
    {
        return $this->isNotificationOpted;
    }

    public function setIsNotificationOpted(int $isNotificationOpted): self
    {
        $this->isNotificationOpted = $isNotificationOpted;

        return $this;
    }

    public function getIsLDAP(): ?bool
    {
        return $this->isLDAP;
    }

    public function setIsLDAP(bool $isLDAP): self
    {
        $this->isLDAP = $isLDAP;

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

    public function getIsBetaUser(): ?int
    {
        return $this->isBetaUser;
    }

    public function setIsBetaUser(int $isBetaUser): self
    {
        $this->isBetaUser = $isBetaUser;

        return $this;
    }

    public function getEnabled(): ?int
    {
        return $this->enabled;
    }

    public function setEnabled(int $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function setSessionId(?string $sessionId): self
    {
        $this->sessionId = $sessionId;

        return $this;
    }
    // public function isEqualTo(UserInterface $user) {
    //     if (!$user instanceof self) {
    //         return false;
    //     }
    //     if ($this->getPassword() !== $user->getPassword()) {
    //         return false;
    //     }
    //     if ($this->getSalt() !== $user->getSalt()) {
    //         return false;
    //     }
    //     if ($this->getUsername() !== $user->getUsername()) {
    //         return false;
    //     }
    //     return true;
    // }
}
