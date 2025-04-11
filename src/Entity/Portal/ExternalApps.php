<?php

namespace App\Entity\Portal;

use App\Entity\Masters\AppCategory;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Portal\MetaData;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * ExternalApps.
 * @Assert\GroupSequence({"ExternalApps", "Length", "Regex"})
 * @ORM\Table(name="gim.apps")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */

class ExternalApps
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
     * @ORM\Column(name="app_name", type="string",length=50, nullable=true)
     */
    private $appName;

    /**
     * @var string
     * 
     * @ORM\Column(name="app_title", type="string", length=100, nullable=true)
     */
    private $appTitle;

    /**
     * @var string
     * 
     * @ORM\Column(name="client_id", type="guid", nullable=true)
     */
    private $clientId;


    /**
     * @var string
     * 
     * @ORM\Column(name="hmac_key_enc", type="string", nullable=true, length=200, options={"comment":"Encrypted HMAC Key"})
     */
    private $hmacKey;


    /**
     * @var string
     * 
     * @ORM\Column(name="ip_whitelist", type="text", length=255, nullable=true)
     */
    private $ipWhiteList;

     /**
     * @ORM\ManyToOne(targetEntity="FileDetail")
     * @ORM\JoinColumn(name="app_logo_id", referencedColumnName="id", columnDefinition="COMMENT  'Id'")
     */
    private $appLogoId;

     /**
     * @var string
     * 
     * @ORM\Column(name="home_page_url", type="string", length=100, nullable=true)
     */
    private $homeURL;
    /**
     * @var string
     * 
     * @ORM\Column(name="privacy_policy_link", type="text",length=255, nullable=true)
     */
    private $privatePolicyLink;

    /**
     * @var string
     * 
     * @ORM\Column(name="integration_scope", type="string", length=2, options={"default"="PR"})
     */
    private $integrationScope;

    /**
     * @ORM\Column(name="active" , type="boolean", nullable=false, options={"default"="true"})
     */
    private $active;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Masters\AppCategory")
     * @ORM\JoinColumn(name="app_category_id", referencedColumnName="id", nullable=true)
     */
    private $appCategoryId;

     /**
     * @ORM\Column(name="app_cover_image_id", type="integer", nullable=true, options={"comment":"App cover image (optional)"})
     */
    private $appCoverImageId;

     /**
     * @ORM\Column(name="user_count", type="integer", nullable=false, options={"default": 0}, options={"comment":"No of users "})
     */
    private $userCount;

    /**
     * @ORM\Column(name="gateway_integration" , type="boolean", nullable=false, options={"default"=false})
     */
    private $gatewayIntegration;

    /**
     * @ORM\Column(name="chatbot_integration" , type="boolean", nullable=false, options={"default"=false})
     */
    private $chatbotIntegration;

    /**
     * @ORM\Column(name="subscription_integration" , type="boolean", nullable=false, options={"default"=false})
     */
    private $subscriptionIntegration;


    /**
     * @ORM\Column(name="auth_integration" , type="boolean",  options={"default"=false})
     */
    private $authIntegration;

    /**
     * @var string
     * 
     * @ORM\Column(name="gateway_jid", type="string", length=254, nullable=true)
     */
    private $gatewayJid;

    /**
     * @var string
     * 
     * @ORM\Column(name="chatbot_jid", type="string", length=254, nullable=true)
     */
    private $chatbotJid;

    /**
     * @var string
     * 
     * @ORM\Column(name="subscription_jid", type="string", length=254, nullable=true)
     */
    private $subscriptionJid;

    /**
     * @var string
     * 
     * @ORM\Column(name="auth_jid", type="string", length=254, nullable=true)
     */
    private $authJid;

    /**
     * @var string
     * 
     * @ORM\Column(name="app_description", type="string", length=1024, nullable=true)
     */
    private $appDescription;

    /**
     * @var string
     * 
     * @ORM\Column(name="app_version", type="string", length=10, nullable=true)
     */
    private $appVersion;

     /**
     * @ORM\Column(name="parent_ou_id", type="integer", nullable=false)
     */
    private $parentOuId;
    /**
     * @ORM\Column(name="rate_limiter_id", type="integer", options={"default"="1"}, nullable=true)
     */
    private $ratelimiterId;

    /**
     * @ORM\Column(name="allow_portal_messaging" , type="boolean", nullable=false)
     */
    private $allowPortalMessaging;

    
    public function getId(): ?int
    {
        return $this->id;
    }

    
    public function getAppName(): ?string
    {
        return $this->appName;
    }

    public function setAppName(string $appName): self
    {
        $this->appName = $appName;

        return $this;
    }

    public function getAppTitle(): ?string
    {
        return $this->appTitle;
    }

    public function setAppTitle(string $appTitle): self
    {
        $this->appTitle = $appTitle;

        return $this;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): self
    {
        $this->clientId = $clientId;

        return $this;
    }

    public function getHmacKey(): ?string
    {
        return $this->hmacKey;
    }

    public function setHmacKey(string $hmacKey): self
    {
        $this->hmacKey = $hmacKey;

        return $this;
    }

    public function getIpWhiteList(): ?string
    {
        return $this->ipWhiteList;
    }

    public function setIpWhiteList(string $ipWhiteList): self
    {
        $this->ipWhiteList = $ipWhiteList;

        return $this;
    }

    public function getAppLogoId(): ?int
    {
        return $this->appLogoId;
    }

    public function setAppLogoId(?int $appLogoId): self
    {
        $this->appLogoId = $appLogoId;

        return $this;
    }

    public function getHomeURL(): ?string
    {
        return $this->homeURL;
    }

    public function setHomeURL(string $homeURL): self
    {
        $this->homeURL = $homeURL;

        return $this;
    }

    public function getPrivatePolicyLink(): ?string
    {
        return $this->privatePolicyLink;
    }

    public function setPrivatePolicyLink(string $privatePolicyLink): self
    {
        $this->privatePolicyLink = $privatePolicyLink;

        return $this;
    }

    public function getIntegrationScope(): ?string
    {
        return $this->integrationScope;
    }

    public function setIntegrationScope(string $integrationScope): self
    {
        $this->integrationScope = $integrationScope;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getAppCategoryId(): ?int
    {
        return $this->appCategoryId;
    }

    public function setAppCategoryId(?int $appCategoryId): self
    {
        $this->appCategoryId = $appCategoryId;

        return $this;
    }

    public function getAppCoverImageId(): ?int
    {
        return $this->appCoverImageId;
    }

    public function setAppCoverImageId(?int $appCoverImageId): self
    {
        $this->appCoverImageId = $appCoverImageId;

        return $this;
    }

    public function getUserCount(): ?int
    {
        return $this->userCount;
    }

    public function setUserCount(?int $userCount): self
    {
        $this->userCount = $userCount;

        return $this;
    }

    public function getGatewayIntegration(): ?bool
    {
        return $this->gatewayIntegration;
    }

    public function setGatewayIntegration(bool $gatewayIntegration): self
    {
        $this->gatewayIntegration = $gatewayIntegration;

        return $this;
    }

    public function getChatbotIntegration(): ?bool
    {
        return $this->chatbotIntegration;
    }

    public function setChatbotIntegration(bool $chatbotIntegration): self
    {
        $this->chatbotIntegration = $chatbotIntegration;

        return $this;
    }

    public function getSubscriptionIntegration(): ?bool
    {
        return $this->subscriptionIntegration;
    }

    public function setSubscriptionIntegration(bool $subscriptionIntegration): self
    {
        $this->subscriptionIntegration = $subscriptionIntegration;

        return $this;
    }

    public function getAuthIntegration(): ?bool
    {
        return $this->authIntegration;
    }

    public function setAuthIntegration(bool $authIntegration): self
    {
        $this->authIntegration = $authIntegration;

        return $this;
    }

    public function getGatewayJid(): ?string
    {
        return $this->gatewayJid;
    }

    public function setGatewayJid(string $gatewayJid): self
    {
        $this->gatewayJid = $gatewayJid;

        return $this;
    }

    public function getChatbotJid(): ?string
    {
        return $this->chatbotJid;
    }

    public function setChatbotJid(string $chatbotJid): self
    {
        $this->chatbotJid = $chatbotJid;

        return $this;
    }

    public function getSubscriptionJid(): ?string
    {
        return $this->subscriptionJid;
    }

    public function setSubscriptionJid(string $subscriptionJid): self
    {
        $this->subscriptionJid = $subscriptionJid;

        return $this;
    }

    public function getAuthJid(): ?string
    {
        return $this->authJid;
    }

    public function setAuthJid(string $authJid): self
    {
        $this->authJid = $authJid;

        return $this;
    }

    public function getAppDescription(): ?string
    {
        return $this->appDescription;
    }

    public function setAppDescription(string $appDescription): self
    {
        $this->appDescription = $appDescription;

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

    public function getParentOuId(): ?int
    {
        return $this->parentOuId;
    }

    public function setParentOuId(?int $parentOuId): self
    {
        $this->parentOuId = $parentOuId;

        return $this;
    }

    public function getRatelimiterId(): ?int
    {
        return $this->ratelimiterId;
    }

    public function setRatelimiterId(?int $ratelimiterId): self
    {
        $this->ratelimiterId = $ratelimiterId;

        return $this;
    }

    public function getAllowPortalMessaging(): ?bool
    {
        return $this->allowPortalMessaging;
    }

    public function setAllowPortalMessaging(bool $allowPortalMessaging): self
    {
        $this->allowPortalMessaging = $allowPortalMessaging;

        return $this;
    }
}