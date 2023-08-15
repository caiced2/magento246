<?php
namespace Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product;

/**
 * Interceptor class for @see \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product
 */
class Interceptor extends \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Action\Row $ruleProductIndexerRow, \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Action\Rows $ruleProductIndexerRows, \Magento\TargetRule\Model\Indexer\TargetRule\Action\Full $ruleProductIndexerFull, \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor $productRuleProcessor, \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor $ruleProductProcessor)
    {
        $this->___init();
        parent::__construct($ruleProductIndexerRow, $ruleProductIndexerRows, $ruleProductIndexerFull, $productRuleProcessor, $ruleProductProcessor);
    }

    /**
     * {@inheritdoc}
     */
    public function execute($ruleId)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'execute');
        return $pluginInfo ? $this->___callPlugins('execute', func_get_args(), $pluginInfo) : parent::execute($ruleId);
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
    public function executeList(array $ruleIds)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'executeList');
        return $pluginInfo ? $this->___callPlugins('executeList', func_get_args(), $pluginInfo) : parent::executeList($ruleIds);
    }

    /**
     * {@inheritdoc}
     */
    public function executeRow($ruleId)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'executeRow');
        return $pluginInfo ? $this->___callPlugins('executeRow', func_get_args(), $pluginInfo) : parent::executeRow($ruleId);
    }
}
