<?php
namespace Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule;

/**
 * Interceptor class for @see \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule
 */
class Interceptor extends \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\Row $productRuleIndexerRow, \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\Rows $productRuleIndexerRows, \Magento\TargetRule\Model\Indexer\TargetRule\Action\Full $productRuleIndexerFull, \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor $ruleProductProcessor, \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor $productRuleProcessor, \Magento\TargetRule\Model\Indexer\TargetRule\Action\Clean $productRuleIndexerClean, \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\CleanDeleteProduct $productRuleIndexerCleanDeleteProduct)
    {
        $this->___init();
        parent::__construct($productRuleIndexerRow, $productRuleIndexerRows, $productRuleIndexerFull, $ruleProductProcessor, $productRuleProcessor, $productRuleIndexerClean, $productRuleIndexerCleanDeleteProduct);
    }

    /**
     * {@inheritdoc}
     */
    public function execute($productIds)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'execute');
        return $pluginInfo ? $this->___callPlugins('execute', func_get_args(), $pluginInfo) : parent::execute($productIds);
    }

    /**
     * {@inheritdoc}
     */
    public function executeFull()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'executeFull');
        return $pluginInfo ? $this->___callPlugins('executeFull', func_get_args(), $pluginInfo) : parent::executeFull();
    }

    /**
     * {@inheritdoc}
     */
    public function executeList(array $productIds)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'executeList');
        return $pluginInfo ? $this->___callPlugins('executeList', func_get_args(), $pluginInfo) : parent::executeList($productIds);
    }

    /**
     * {@inheritdoc}
     */
    public function executeRow($productId)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'executeRow');
        return $pluginInfo ? $this->___callPlugins('executeRow', func_get_args(), $pluginInfo) : parent::executeRow($productId);
    }

    /**
     * {@inheritdoc}
     */
    public function cleanByCron()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'cleanByCron');
        return $pluginInfo ? $this->___callPlugins('cleanByCron', func_get_args(), $pluginInfo) : parent::cleanByCron();
    }

    /**
     * {@inheritdoc}
     */
    public function cleanAfterProductDelete($productId)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'cleanAfterProductDelete');
        return $pluginInfo ? $this->___callPlugins('cleanAfterProductDelete', func_get_args(), $pluginInfo) : parent::cleanAfterProductDelete($productId);
    }
}
