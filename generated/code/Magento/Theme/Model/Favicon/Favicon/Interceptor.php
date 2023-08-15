<?php
namespace Magento\Theme\Model\Favicon\Favicon;

/**
 * Interceptor class for @see \Magento\Theme\Model\Favicon\Favicon
 */
class Interceptor extends \Magento\Theme\Model\Favicon\Favicon implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageDatabase, \Magento\Framework\Filesystem $filesystem)
    {
        $this->___init();
        parent::__construct($storeManager, $scopeConfig, $fileStorageDatabase, $filesystem);
    }

    /**
     * {@inheritdoc}
     */
    public function getFaviconFile()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getFaviconFile');
        return $pluginInfo ? $this->___callPlugins('getFaviconFile', func_get_args(), $pluginInfo) : parent::getFaviconFile();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultFavicon()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getDefaultFavicon');
        return $pluginInfo ? $this->___callPlugins('getDefaultFavicon', func_get_args(), $pluginInfo) : parent::getDefaultFavicon();
    }
}
