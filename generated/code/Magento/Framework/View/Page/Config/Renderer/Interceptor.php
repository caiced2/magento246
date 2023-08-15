<?php
namespace Magento\Framework\View\Page\Config\Renderer;

/**
 * Interceptor class for @see \Magento\Framework\View\Page\Config\Renderer
 */
class Interceptor extends \Magento\Framework\View\Page\Config\Renderer implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Page\Config $pageConfig, \Magento\Framework\View\Asset\MergeService $assetMergeService, \Magento\Framework\UrlInterface $urlBuilder, \Magento\Framework\Escaper $escaper, \Magento\Framework\Stdlib\StringUtils $string, \Psr\Log\LoggerInterface $logger, ?\Magento\Framework\View\Page\Config\Metadata\MsApplicationTileImage $msApplicationTileImage = null)
    {
        $this->___init();
        parent::__construct($pageConfig, $assetMergeService, $urlBuilder, $escaper, $string, $logger, $msApplicationTileImage);
    }

    /**
     * {@inheritdoc}
     */
    public function renderElementAttributes($elementType)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'renderElementAttributes');
        return $pluginInfo ? $this->___callPlugins('renderElementAttributes', func_get_args(), $pluginInfo) : parent::renderElementAttributes($elementType);
    }

    /**
     * {@inheritdoc}
     */
    public function renderHeadContent()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'renderHeadContent');
        return $pluginInfo ? $this->___callPlugins('renderHeadContent', func_get_args(), $pluginInfo) : parent::renderHeadContent();
    }

    /**
     * {@inheritdoc}
     */
    public function renderTitle()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'renderTitle');
        return $pluginInfo ? $this->___callPlugins('renderTitle', func_get_args(), $pluginInfo) : parent::renderTitle();
    }

    /**
     * {@inheritdoc}
     */
    public function renderMetadata()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'renderMetadata');
        return $pluginInfo ? $this->___callPlugins('renderMetadata', func_get_args(), $pluginInfo) : parent::renderMetadata();
    }

    /**
     * {@inheritdoc}
     */
    public function prepareFavicon()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'prepareFavicon');
        return $pluginInfo ? $this->___callPlugins('prepareFavicon', func_get_args(), $pluginInfo) : parent::prepareFavicon();
    }

    /**
     * {@inheritdoc}
     */
    public function renderAssets($resultGroups = [])
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'renderAssets');
        return $pluginInfo ? $this->___callPlugins('renderAssets', func_get_args(), $pluginInfo) : parent::renderAssets($resultGroups);
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableResultGroups()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getAvailableResultGroups');
        return $pluginInfo ? $this->___callPlugins('getAvailableResultGroups', func_get_args(), $pluginInfo) : parent::getAvailableResultGroups();
    }
}
