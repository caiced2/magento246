<?php
namespace Magento\Store\Model\StoreResolver;

/**
 * Interceptor class for @see \Magento\Store\Model\StoreResolver
 */
class Interceptor extends \Magento\Store\Model\StoreResolver implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Store\Api\StoreRepositoryInterface $storeRepository, \Magento\Store\Api\StoreCookieManagerInterface $storeCookieManager, \Magento\Framework\App\Request\Http $request, \Magento\Store\Model\StoresData $storesData, \Magento\Store\App\Request\StorePathInfoValidator $storePathInfoValidator, $runMode = 'store', $scopeCode = null)
    {
        $this->___init();
        parent::__construct($storeRepository, $storeCookieManager, $request, $storesData, $storePathInfoValidator, $runMode, $scopeCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentStoreId()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getCurrentStoreId');
        return $pluginInfo ? $this->___callPlugins('getCurrentStoreId', func_get_args(), $pluginInfo) : parent::getCurrentStoreId();
    }
}
