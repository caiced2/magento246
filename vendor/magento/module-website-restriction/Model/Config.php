<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\WebsiteRestriction\Model;

use Magento\Framework\Serialize\SerializerInterface;

/**
 * @api
 * @since 100.0.2
 */
class Config extends \Magento\Framework\Config\Data\Scoped implements \Magento\WebsiteRestriction\Model\ConfigInterface
{
    const XML_PATH_RESTRICTION_ENABLED = 'general/restriction/is_active';

    const XML_PATH_RESTRICTION_MODE = 'general/restriction/mode';

    const XML_PATH_RESTRICTION_LANDING_PAGE = 'general/restriction/cms_page';

    const XML_PATH_RESTRICTION_HTTP_STATUS = 'general/restriction/http_status';

    const XML_PATH_RESTRICTION_HTTP_REDIRECT = 'general/restriction/http_redirect';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Scope priority loading scheme
     *
     * @var string[]
     */
    protected $_scopePriorityScheme = ['global'];

    /**
     * @param \Magento\WebsiteRestriction\Model\Config\Reader $reader
     * @param \Magento\Framework\Config\ScopeInterface $configScope
     * @param \Magento\Framework\Config\CacheInterface $cache
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param string|null $cacheId
     * @param SerializerInterface|null $serializer
     */
    public function __construct(
        \Magento\WebsiteRestriction\Model\Config\Reader $reader,
        \Magento\Framework\Config\ScopeInterface $configScope,
        \Magento\Framework\Config\CacheInterface $cache,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        $cacheId = 'website_restrictions',
        SerializerInterface $serializer = null
    ) {
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($reader, $configScope, $cache, $cacheId, $serializer);
    }

    /**
     * Get generic actions list
     *
     * @return mixed
     */
    public function getGenericActions()
    {
        return $this->get('generic', []);
    }

    /**
     * Get register actions list
     *
     * @return mixed
     */
    public function getRegisterActions()
    {
        return $this->get('register', []);
    }

    /**
     * Define if restriction is active
     *
     * @param \Magento\Store\Model\Store|string|int $store
     * @return bool
     */
    public function isRestrictionEnabled($store = null)
    {
        return (bool)(int)$this->_scopeConfig->getValue(
            self::XML_PATH_RESTRICTION_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get restriction mode
     *
     * @return int
     */
    public function getMode()
    {
        return (int)$this->_scopeConfig->getValue(
            self::XML_PATH_RESTRICTION_MODE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get restriction HTTP status
     *
     * @return int
     */
    public function getHTTPStatusCode()
    {
        return (int)$this->_scopeConfig->getValue(
            self::XML_PATH_RESTRICTION_HTTP_STATUS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get restriction HTTP redirect code
     *
     * @return int
     */
    public function getHTTPRedirectCode()
    {
        return (int)$this->_scopeConfig->getValue(
            self::XML_PATH_RESTRICTION_HTTP_REDIRECT,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get restriction landing page code
     *
     * @return string
     */
    public function getLandingPageCode()
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_RESTRICTION_LANDING_PAGE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
