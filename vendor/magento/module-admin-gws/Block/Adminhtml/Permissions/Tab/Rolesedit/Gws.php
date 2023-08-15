<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Block\Adminhtml\Permissions\Tab\Rolesedit;

/**
 * Websites fieldset for admin roles edit tab
 *
 * @api
 * @since 100.0.2
 */
class Gws extends \Magento\Backend\Block\Template
{
    /**
     * Session keys for Use all resources flag form data
     */
    const SCOPE_ALL_FORM_DATA_SESSION_KEY = 'scope_all_form_data';

    /**
     * Session keys for Resource form data
     */
    const SCOPE_WEBSITE_FORM_DATA_SESSION_KEY = 'scope_website_form_data';

    /**
     * Session keys for Resource form data
     */
    const SCOPE_STORE_FORM_DATA_SESSION_KEY = 'scope_store_form_data';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\AdminGws\Model\Role
     */
    protected $_adminGwsRole;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * @var Session
     * @since 100.1.0
     */
    protected $backendSession;

    /**
     * @var $gwsIsAll
     * @since 100.1.0
     */
    protected $gwsIsAll;

    /**
     * @var $gwsWebsites
     * @since 100.1.0
     */
    protected $gwsWebsites;

    /**
     * @var $gwsStores
     * @since 100.1.0
     */
    protected $gwsStores;

    /**
     * Gws constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\AdminGws\Model\Role $adminGwsRole
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\AdminGws\Model\Role $adminGwsRole,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_jsonEncoder = $jsonEncoder;
        $this->_adminGwsRole = $adminGwsRole;
        $this->_coreRegistry = $coreRegistry;
        $this->backendSession = $context->getBackendSession();
        $this->gwsIsAll = $this->backendSession->getData(self::SCOPE_ALL_FORM_DATA_SESSION_KEY, true);
        $this->gwsWebsites = $this->backendSession->getData(self::SCOPE_WEBSITE_FORM_DATA_SESSION_KEY, true);
        $this->gwsStores = $this->backendSession->getData(self::SCOPE_STORE_FORM_DATA_SESSION_KEY, true);
    }

    /**
     * Check whether role assumes all websites permissions and restore role data from session
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getGwsIsAll()
    {
        if ((!$this->canAssignGwsAll() || $this->gwsWebsites || $this->gwsStores) && !$this->gwsIsAll) {
            return false;
        }

        if (!$this->_coreRegistry->registry('current_role')->getId()) {
            return true;
        }

        return $this->_coreRegistry->registry('current_role')->getGwsIsAll();
    }

    /**
     * Get the role object
     *
     * @return \Magento\Authorization\Model\Role
     */
    protected function getRole()
    {
        return $this->_coreRegistry->registry('current_role');
    }

    /**
     * Check an ability to create 'no website restriction' roles
     *
     * @return bool
     */
    public function canAssignGwsAll()
    {
        return $this->_adminGwsRole->getIsAll();
    }

    /**
     * Gather disallowed store group ids and return them as Json
     *
     * @return string
     */
    public function getDisallowedStoreGroupsJson()
    {
        $result = [];
        foreach ($this->_storeManager->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $groupId = $group->getId();
                if (!$this->_adminGwsRole->hasStoreGroupAccess($groupId)) {
                    $result[$groupId] = $groupId;
                }
            }
        }
        return $this->_jsonEncoder->encode($result);
    }

    /**
     * Get websites
     *
     * @return \Magento\Store\Model\Website[]
     */
    public function getWebsites()
    {
        return $this->_storeManager->getWebsites();
    }

    /**
     * Get GWS Websites
     *
     * @return array
     * @since 100.1.0
     */
    public function getGwsWebsites()
    {
        if ($this->gwsWebsites) {
            return $this->gwsWebsites;
        }
        return $this->getRole()->getGwsWebsites();
    }

    /**
     * Get GWS Stores
     *
     * @return array
     * @since 100.1.0
     */
    public function getGwsStoreGroups()
    {
        if ($this->gwsStores) {
            return $this->gwsStores;
        }
        return $this->getRole()->getGwsStoreGroups();
    }
}
