<?php
namespace Magento\Store\Model\WebsiteRepository;

/**
 * Interceptor class for @see \Magento\Store\Model\WebsiteRepository
 */
class Interceptor extends \Magento\Store\Model\WebsiteRepository implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Store\Model\WebsiteFactory $factory, \Magento\Store\Model\ResourceModel\Website\CollectionFactory $websiteCollectionFactory)
    {
        $this->___init();
        parent::__construct($factory, $websiteCollectionFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function get($code)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'get');
        return $pluginInfo ? $this->___callPlugins('get', func_get_args(), $pluginInfo) : parent::get($code);
    }

    /**
     * {@inheritdoc}
     */
    public function getById($id)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getById');
        return $pluginInfo ? $this->___callPlugins('getById', func_get_args(), $pluginInfo) : parent::getById($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getList()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getList');
        return $pluginInfo ? $this->___callPlugins('getList', func_get_args(), $pluginInfo) : parent::getList();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getDefault');
        return $pluginInfo ? $this->___callPlugins('getDefault', func_get_args(), $pluginInfo) : parent::getDefault();
    }

    /**
     * {@inheritdoc}
     */
    public function clean()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'clean');
        return $pluginInfo ? $this->___callPlugins('clean', func_get_args(), $pluginInfo) : parent::clean();
    }
}
