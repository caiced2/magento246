<?php
namespace Magento\CatalogInventory\Model\Configuration;

/**
 * Interceptor class for @see \Magento\CatalogInventory\Model\Configuration
 */
class Interceptor extends \Magento\CatalogInventory\Model\Configuration implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Catalog\Model\ProductTypes\ConfigInterface $config, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\CatalogInventory\Helper\Minsaleqty $minsaleqtyHelper, \Magento\Store\Model\StoreManagerInterface $storeManager)
    {
        $this->___init();
        parent::__construct($config, $scopeConfig, $minsaleqtyHelper, $storeManager);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultScopeId()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getDefaultScopeId');
        return $pluginInfo ? $this->___callPlugins('getDefaultScopeId', func_get_args(), $pluginInfo) : parent::getDefaultScopeId();
    }

    /**
     * {@inheritdoc}
     */
    public function getIsQtyTypeIds($filter = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getIsQtyTypeIds');
        return $pluginInfo ? $this->___callPlugins('getIsQtyTypeIds', func_get_args(), $pluginInfo) : parent::getIsQtyTypeIds($filter);
    }

    /**
     * {@inheritdoc}
     */
    public function isQty($productTypeId)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isQty');
        return $pluginInfo ? $this->___callPlugins('isQty', func_get_args(), $pluginInfo) : parent::isQty($productTypeId);
    }

    /**
     * {@inheritdoc}
     */
    public function canSubtractQty($store = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'canSubtractQty');
        return $pluginInfo ? $this->___callPlugins('canSubtractQty', func_get_args(), $pluginInfo) : parent::canSubtractQty($store);
    }

    /**
     * {@inheritdoc}
     */
    public function getMinQty($store = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getMinQty');
        return $pluginInfo ? $this->___callPlugins('getMinQty', func_get_args(), $pluginInfo) : parent::getMinQty($store);
    }

    /**
     * {@inheritdoc}
     */
    public function getMinSaleQty($store = null, $customerGroupId = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getMinSaleQty');
        return $pluginInfo ? $this->___callPlugins('getMinSaleQty', func_get_args(), $pluginInfo) : parent::getMinSaleQty($store, $customerGroupId);
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxSaleQty($store = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getMaxSaleQty');
        return $pluginInfo ? $this->___callPlugins('getMaxSaleQty', func_get_args(), $pluginInfo) : parent::getMaxSaleQty($store);
    }

    /**
     * {@inheritdoc}
     */
    public function getNotifyStockQty($store = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getNotifyStockQty');
        return $pluginInfo ? $this->___callPlugins('getNotifyStockQty', func_get_args(), $pluginInfo) : parent::getNotifyStockQty($store);
    }

    /**
     * {@inheritdoc}
     */
    public function getEnableQtyIncrements($store = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getEnableQtyIncrements');
        return $pluginInfo ? $this->___callPlugins('getEnableQtyIncrements', func_get_args(), $pluginInfo) : parent::getEnableQtyIncrements($store);
    }

    /**
     * {@inheritdoc}
     */
    public function getQtyIncrements($store = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getQtyIncrements');
        return $pluginInfo ? $this->___callPlugins('getQtyIncrements', func_get_args(), $pluginInfo) : parent::getQtyIncrements($store);
    }

    /**
     * {@inheritdoc}
     */
    public function getBackorders($store = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getBackorders');
        return $pluginInfo ? $this->___callPlugins('getBackorders', func_get_args(), $pluginInfo) : parent::getBackorders($store);
    }

    /**
     * {@inheritdoc}
     */
    public function getManageStock($store = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getManageStock');
        return $pluginInfo ? $this->___callPlugins('getManageStock', func_get_args(), $pluginInfo) : parent::getManageStock($store);
    }

    /**
     * {@inheritdoc}
     */
    public function getCanBackInStock($store = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getCanBackInStock');
        return $pluginInfo ? $this->___callPlugins('getCanBackInStock', func_get_args(), $pluginInfo) : parent::getCanBackInStock($store);
    }

    /**
     * {@inheritdoc}
     */
    public function isShowOutOfStock($store = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isShowOutOfStock');
        return $pluginInfo ? $this->___callPlugins('isShowOutOfStock', func_get_args(), $pluginInfo) : parent::isShowOutOfStock($store);
    }

    /**
     * {@inheritdoc}
     */
    public function isAutoReturnEnabled($store = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isAutoReturnEnabled');
        return $pluginInfo ? $this->___callPlugins('isAutoReturnEnabled', func_get_args(), $pluginInfo) : parent::isAutoReturnEnabled($store);
    }

    /**
     * {@inheritdoc}
     */
    public function isDisplayProductStockStatus($store = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isDisplayProductStockStatus');
        return $pluginInfo ? $this->___callPlugins('isDisplayProductStockStatus', func_get_args(), $pluginInfo) : parent::isDisplayProductStockStatus($store);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultConfigValue($field, $store = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getDefaultConfigValue');
        return $pluginInfo ? $this->___callPlugins('getDefaultConfigValue', func_get_args(), $pluginInfo) : parent::getDefaultConfigValue($field, $store);
    }

    /**
     * {@inheritdoc}
     */
    public function getStockThresholdQty($store = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getStockThresholdQty');
        return $pluginInfo ? $this->___callPlugins('getStockThresholdQty', func_get_args(), $pluginInfo) : parent::getStockThresholdQty($store);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigItemOptions()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getConfigItemOptions');
        return $pluginInfo ? $this->___callPlugins('getConfigItemOptions', func_get_args(), $pluginInfo) : parent::getConfigItemOptions();
    }
}
