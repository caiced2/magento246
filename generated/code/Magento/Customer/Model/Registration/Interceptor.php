<?php
namespace Magento\Customer\Model\Registration;

/**
 * Interceptor class for @see \Magento\Customer\Model\Registration
 */
class Interceptor extends \Magento\Customer\Model\Registration implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct()
    {
        $this->___init();
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowed()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isAllowed');
        return $pluginInfo ? $this->___callPlugins('isAllowed', func_get_args(), $pluginInfo) : parent::isAllowed();
    }
}
