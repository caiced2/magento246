<?php
namespace Magento\AdvancedCheckout\Model\AreProductsSalableForRequestedQty;

/**
 * Interceptor class for @see \Magento\AdvancedCheckout\Model\AreProductsSalableForRequestedQty
 */
class Interceptor extends \Magento\AdvancedCheckout\Model\AreProductsSalableForRequestedQty implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry, \Magento\AdvancedCheckout\Model\Data\IsProductsSalableForRequestedQtyResultFactory $salableForRequestedQtyResultFactory)
    {
        $this->___init();
        parent::__construct($stockRegistry, $salableForRequestedQtyResultFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $productQuantities, int $websiteId) : array
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'execute');
        return $pluginInfo ? $this->___callPlugins('execute', func_get_args(), $pluginInfo) : parent::execute($productQuantities, $websiteId);
    }
}
