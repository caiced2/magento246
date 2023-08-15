<?php
namespace Magento\GoogleTagManager\Helper\Data;

/**
 * Interceptor class for @see \Magento\GoogleTagManager\Helper\Data
 */
class Interceptor extends \Magento\GoogleTagManager\Helper\Data implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Helper\Context $context)
    {
        $this->___init();
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function isGoogleAnalyticsAvailable($store = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isGoogleAnalyticsAvailable');
        return $pluginInfo ? $this->___callPlugins('isGoogleAnalyticsAvailable', func_get_args(), $pluginInfo) : parent::isGoogleAnalyticsAvailable($store);
    }

    /**
     * {@inheritdoc}
     */
    public function isTagManagerAvailable($store = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isTagManagerAvailable');
        return $pluginInfo ? $this->___callPlugins('isTagManagerAvailable', func_get_args(), $pluginInfo) : parent::isTagManagerAvailable($store);
    }

    /**
     * {@inheritdoc}
     */
    public function isAnonymizedIpActive($store = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isAnonymizedIpActive');
        return $pluginInfo ? $this->___callPlugins('isAnonymizedIpActive', func_get_args(), $pluginInfo) : parent::isAnonymizedIpActive($store);
    }

    /**
     * {@inheritdoc}
     */
    public function isModuleOutputEnabled($moduleName = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isModuleOutputEnabled');
        return $pluginInfo ? $this->___callPlugins('isModuleOutputEnabled', func_get_args(), $pluginInfo) : parent::isModuleOutputEnabled($moduleName);
    }
}
