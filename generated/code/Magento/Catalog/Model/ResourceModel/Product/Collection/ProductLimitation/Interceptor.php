<?php
namespace Magento\Catalog\Model\ResourceModel\Product\Collection\ProductLimitation;

/**
 * Interceptor class for @see \Magento\Catalog\Model\ResourceModel\Product\Collection\ProductLimitation
 */
class Interceptor extends \Magento\Catalog\Model\ResourceModel\Product\Collection\ProductLimitation implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct()
    {
        $this->___init();
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'offsetExists');
        return $pluginInfo ? $this->___callPlugins('offsetExists', func_get_args(), $pluginInfo) : parent::offsetExists($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'offsetGet');
        return $pluginInfo ? $this->___callPlugins('offsetGet', func_get_args(), $pluginInfo) : parent::offsetGet($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'offsetSet');
        return $pluginInfo ? $this->___callPlugins('offsetSet', func_get_args(), $pluginInfo) : parent::offsetSet($offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'offsetUnset');
        return $pluginInfo ? $this->___callPlugins('offsetUnset', func_get_args(), $pluginInfo) : parent::offsetUnset($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function getStoreId()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getStoreId');
        return $pluginInfo ? $this->___callPlugins('getStoreId', func_get_args(), $pluginInfo) : parent::getStoreId();
    }

    /**
     * {@inheritdoc}
     */
    public function getCategoryId()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getCategoryId');
        return $pluginInfo ? $this->___callPlugins('getCategoryId', func_get_args(), $pluginInfo) : parent::getCategoryId();
    }

    /**
     * {@inheritdoc}
     */
    public function getCategoryIsAnchor()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getCategoryIsAnchor');
        return $pluginInfo ? $this->___callPlugins('getCategoryIsAnchor', func_get_args(), $pluginInfo) : parent::getCategoryIsAnchor();
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibility()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getVisibility');
        return $pluginInfo ? $this->___callPlugins('getVisibility', func_get_args(), $pluginInfo) : parent::getVisibility();
    }

    /**
     * {@inheritdoc}
     */
    public function getWebsiteIds()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getWebsiteIds');
        return $pluginInfo ? $this->___callPlugins('getWebsiteIds', func_get_args(), $pluginInfo) : parent::getWebsiteIds();
    }

    /**
     * {@inheritdoc}
     */
    public function getStoreTable()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getStoreTable');
        return $pluginInfo ? $this->___callPlugins('getStoreTable', func_get_args(), $pluginInfo) : parent::getStoreTable();
    }

    /**
     * {@inheritdoc}
     */
    public function isUsingPriceIndex()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isUsingPriceIndex');
        return $pluginInfo ? $this->___callPlugins('isUsingPriceIndex', func_get_args(), $pluginInfo) : parent::isUsingPriceIndex();
    }

    /**
     * {@inheritdoc}
     */
    public function setUsePriceIndex($value)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setUsePriceIndex');
        return $pluginInfo ? $this->___callPlugins('setUsePriceIndex', func_get_args(), $pluginInfo) : parent::setUsePriceIndex($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerGroupId()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getCustomerGroupId');
        return $pluginInfo ? $this->___callPlugins('getCustomerGroupId', func_get_args(), $pluginInfo) : parent::getCustomerGroupId();
    }

    /**
     * {@inheritdoc}
     */
    public function getWebsiteId()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getWebsiteId');
        return $pluginInfo ? $this->___callPlugins('getWebsiteId', func_get_args(), $pluginInfo) : parent::getWebsiteId();
    }
}
