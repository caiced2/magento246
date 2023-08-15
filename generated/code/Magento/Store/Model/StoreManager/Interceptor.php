<?php
namespace Magento\Store\Model\StoreManager;

/**
 * Interceptor class for @see \Magento\Store\Model\StoreManager
 */
class Interceptor extends \Magento\Store\Model\StoreManager implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Store\Api\StoreRepositoryInterface $storeRepository, \Magento\Store\Api\GroupRepositoryInterface $groupRepository, \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Store\Api\StoreResolverInterface $storeResolver, \Magento\Framework\Cache\FrontendInterface $cache, $isSingleStoreAllowed = true)
    {
        $this->___init();
        parent::__construct($storeRepository, $groupRepository, $websiteRepository, $scopeConfig, $storeResolver, $cache, $isSingleStoreAllowed);
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrentStore($store)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setCurrentStore');
        return $pluginInfo ? $this->___callPlugins('setCurrentStore', func_get_args(), $pluginInfo) : parent::setCurrentStore($store);
    }

    /**
     * {@inheritdoc}
     */
    public function setIsSingleStoreModeAllowed($value)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setIsSingleStoreModeAllowed');
        return $pluginInfo ? $this->___callPlugins('setIsSingleStoreModeAllowed', func_get_args(), $pluginInfo) : parent::setIsSingleStoreModeAllowed($value);
    }

    /**
     * {@inheritdoc}
     */
    public function hasSingleStore()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'hasSingleStore');
        return $pluginInfo ? $this->___callPlugins('hasSingleStore', func_get_args(), $pluginInfo) : parent::hasSingleStore();
    }

    /**
     * {@inheritdoc}
     */
    public function isSingleStoreMode()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isSingleStoreMode');
        return $pluginInfo ? $this->___callPlugins('isSingleStoreMode', func_get_args(), $pluginInfo) : parent::isSingleStoreMode();
    }

    /**
     * {@inheritdoc}
     */
    public function getStore($storeId = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getStore');
        return $pluginInfo ? $this->___callPlugins('getStore', func_get_args(), $pluginInfo) : parent::getStore($storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getStores($withDefault = false, $codeKey = false)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getStores');
        return $pluginInfo ? $this->___callPlugins('getStores', func_get_args(), $pluginInfo) : parent::getStores($withDefault, $codeKey);
    }

    /**
     * {@inheritdoc}
     */
    public function getWebsite($websiteId = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getWebsite');
        return $pluginInfo ? $this->___callPlugins('getWebsite', func_get_args(), $pluginInfo) : parent::getWebsite($websiteId);
    }

    /**
     * {@inheritdoc}
     */
    public function getWebsites($withDefault = false, $codeKey = false)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getWebsites');
        return $pluginInfo ? $this->___callPlugins('getWebsites', func_get_args(), $pluginInfo) : parent::getWebsites($withDefault, $codeKey);
    }

    /**
     * {@inheritdoc}
     */
    public function reinitStores()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'reinitStores');
        return $pluginInfo ? $this->___callPlugins('reinitStores', func_get_args(), $pluginInfo) : parent::reinitStores();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultStoreView()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getDefaultStoreView');
        return $pluginInfo ? $this->___callPlugins('getDefaultStoreView', func_get_args(), $pluginInfo) : parent::getDefaultStoreView();
    }

    /**
     * {@inheritdoc}
     */
    public function getGroup($groupId = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getGroup');
        return $pluginInfo ? $this->___callPlugins('getGroup', func_get_args(), $pluginInfo) : parent::getGroup($groupId);
    }

    /**
     * {@inheritdoc}
     */
    public function getGroups($withDefault = false)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getGroups');
        return $pluginInfo ? $this->___callPlugins('getGroups', func_get_args(), $pluginInfo) : parent::getGroups($withDefault);
    }

    /**
     * {@inheritdoc}
     */
    public function getStoreByWebsiteId($websiteId)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getStoreByWebsiteId');
        return $pluginInfo ? $this->___callPlugins('getStoreByWebsiteId', func_get_args(), $pluginInfo) : parent::getStoreByWebsiteId($websiteId);
    }
}
