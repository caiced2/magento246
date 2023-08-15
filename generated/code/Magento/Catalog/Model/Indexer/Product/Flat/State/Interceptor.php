<?php
namespace Magento\Catalog\Model\Indexer\Product\Flat\State;

/**
 * Interceptor class for @see \Magento\Catalog\Model\Indexer\Product\Flat\State
 */
class Interceptor extends \Magento\Catalog\Model\Indexer\Product\Flat\State implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry, \Magento\Catalog\Helper\Product\Flat\Indexer $flatIndexerHelper, $isAvailable = false)
    {
        $this->___init();
        parent::__construct($scopeConfig, $indexerRegistry, $flatIndexerHelper, $isAvailable);
    }

    /**
     * {@inheritdoc}
     */
    public function getFlatIndexerHelper()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getFlatIndexerHelper');
        return $pluginInfo ? $this->___callPlugins('getFlatIndexerHelper', func_get_args(), $pluginInfo) : parent::getFlatIndexerHelper();
    }

    /**
     * {@inheritdoc}
     */
    public function isFlatEnabled()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isFlatEnabled');
        return $pluginInfo ? $this->___callPlugins('isFlatEnabled', func_get_args(), $pluginInfo) : parent::isFlatEnabled();
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isAvailable');
        return $pluginInfo ? $this->___callPlugins('isAvailable', func_get_args(), $pluginInfo) : parent::isAvailable();
    }
}
