<?php
namespace Magento\AdvancedSalesRule\Model\Indexer\SalesRule;

/**
 * Interceptor class for @see \Magento\AdvancedSalesRule\Model\Indexer\SalesRule
 */
class Interceptor extends \Magento\AdvancedSalesRule\Model\Indexer\SalesRule implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Action\FullFactory $fullActionFactory, \Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Action\RowsFactory $rowsActionFactory)
    {
        $this->___init();
        parent::__construct($fullActionFactory, $rowsActionFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function execute($ids)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'execute');
        return $pluginInfo ? $this->___callPlugins('execute', func_get_args(), $pluginInfo) : parent::execute($ids);
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
    public function executeList(array $ids)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'executeList');
        return $pluginInfo ? $this->___callPlugins('executeList', func_get_args(), $pluginInfo) : parent::executeList($ids);
    }

    /**
     * {@inheritdoc}
     */
    public function executeRow($id)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'executeRow');
        return $pluginInfo ? $this->___callPlugins('executeRow', func_get_args(), $pluginInfo) : parent::executeRow($id);
    }
}
