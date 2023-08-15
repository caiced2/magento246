<?php
namespace Magento\Captcha\Helper\Data;

/**
 * Interceptor class for @see \Magento\Captcha\Helper\Data
 */
class Interceptor extends \Magento\Captcha\Helper\Data implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Helper\Context $context, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Framework\Filesystem $filesystem, \Magento\Captcha\Model\CaptchaFactory $factory)
    {
        $this->___init();
        parent::__construct($context, $storeManager, $filesystem, $factory);
    }

    /**
     * {@inheritdoc}
     */
    public function getCaptcha($formId)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getCaptcha');
        return $pluginInfo ? $this->___callPlugins('getCaptcha', func_get_args(), $pluginInfo) : parent::getCaptcha($formId);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig($key, $store = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getConfig');
        return $pluginInfo ? $this->___callPlugins('getConfig', func_get_args(), $pluginInfo) : parent::getConfig($key, $store);
    }

    /**
     * {@inheritdoc}
     */
    public function getFonts()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getFonts');
        return $pluginInfo ? $this->___callPlugins('getFonts', func_get_args(), $pluginInfo) : parent::getFonts();
    }

    /**
     * {@inheritdoc}
     */
    public function getImgDir($website = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getImgDir');
        return $pluginInfo ? $this->___callPlugins('getImgDir', func_get_args(), $pluginInfo) : parent::getImgDir($website);
    }

    /**
     * {@inheritdoc}
     */
    public function getImgUrl($website = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getImgUrl');
        return $pluginInfo ? $this->___callPlugins('getImgUrl', func_get_args(), $pluginInfo) : parent::getImgUrl($website);
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
