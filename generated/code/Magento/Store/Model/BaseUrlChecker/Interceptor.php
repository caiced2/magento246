<?php
namespace Magento\Store\Model\BaseUrlChecker;

/**
 * Interceptor class for @see \Magento\Store\Model\BaseUrlChecker
 */
class Interceptor extends \Magento\Store\Model\BaseUrlChecker implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->___init();
        parent::__construct($scopeConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function execute($uri, $request)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'execute');
        return $pluginInfo ? $this->___callPlugins('execute', func_get_args(), $pluginInfo) : parent::execute($uri, $request);
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isEnabled');
        return $pluginInfo ? $this->___callPlugins('isEnabled', func_get_args(), $pluginInfo) : parent::isEnabled();
    }

    /**
     * {@inheritdoc}
     */
    public function isFrontendSecure()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isFrontendSecure');
        return $pluginInfo ? $this->___callPlugins('isFrontendSecure', func_get_args(), $pluginInfo) : parent::isFrontendSecure();
    }
}
