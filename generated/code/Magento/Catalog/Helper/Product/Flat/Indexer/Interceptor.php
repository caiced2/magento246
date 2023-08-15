<?php
namespace Magento\Catalog\Helper\Product\Flat\Indexer;

/**
 * Interceptor class for @see \Magento\Catalog\Helper\Product\Flat\Indexer
 */
class Interceptor extends \Magento\Catalog\Helper\Product\Flat\Indexer implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Helper\Context $context, \Magento\Framework\App\ResourceConnection $resource, \Magento\Eav\Model\Config $eavConfig, \Magento\Catalog\Model\Attribute\Config $attributeConfig, \Magento\Catalog\Model\ResourceModel\ConfigFactory $configFactory, \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Framework\Mview\View\Changelog $changelog, $addFilterableAttrs = false, $addChildData = false, $flatAttributeGroups = [])
    {
        $this->___init();
        parent::__construct($context, $resource, $eavConfig, $attributeConfig, $configFactory, $attributeFactory, $storeManager, $changelog, $addFilterableAttrs, $addChildData, $flatAttributeGroups);
    }

    /**
     * {@inheritdoc}
     */
    public function getFlatColumnsDdlDefinition()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getFlatColumnsDdlDefinition');
        return $pluginInfo ? $this->___callPlugins('getFlatColumnsDdlDefinition', func_get_args(), $pluginInfo) : parent::getFlatColumnsDdlDefinition();
    }

    /**
     * {@inheritdoc}
     */
    public function isAddFilterableAttributes()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isAddFilterableAttributes');
        return $pluginInfo ? $this->___callPlugins('isAddFilterableAttributes', func_get_args(), $pluginInfo) : parent::isAddFilterableAttributes();
    }

    /**
     * {@inheritdoc}
     */
    public function isAddChildData()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isAddChildData');
        return $pluginInfo ? $this->___callPlugins('isAddChildData', func_get_args(), $pluginInfo) : parent::isAddChildData();
    }

    /**
     * {@inheritdoc}
     */
    public function getFlatColumns()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getFlatColumns');
        return $pluginInfo ? $this->___callPlugins('getFlatColumns', func_get_args(), $pluginInfo) : parent::getFlatColumns();
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityType()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getEntityType');
        return $pluginInfo ? $this->___callPlugins('getEntityType', func_get_args(), $pluginInfo) : parent::getEntityType();
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityTypeId()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getEntityTypeId');
        return $pluginInfo ? $this->___callPlugins('getEntityTypeId', func_get_args(), $pluginInfo) : parent::getEntityTypeId();
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getAttributes');
        return $pluginInfo ? $this->___callPlugins('getAttributes', func_get_args(), $pluginInfo) : parent::getAttributes();
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeCodes()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getAttributeCodes');
        return $pluginInfo ? $this->___callPlugins('getAttributeCodes', func_get_args(), $pluginInfo) : parent::getAttributeCodes();
    }

    /**
     * {@inheritdoc}
     */
    public function getFlatIndexes()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getFlatIndexes');
        return $pluginInfo ? $this->___callPlugins('getFlatIndexes', func_get_args(), $pluginInfo) : parent::getFlatIndexes();
    }

    /**
     * {@inheritdoc}
     */
    public function getTablesStructure(array $attributes)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getTablesStructure');
        return $pluginInfo ? $this->___callPlugins('getTablesStructure', func_get_args(), $pluginInfo) : parent::getTablesStructure($attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function getTable($name)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getTable');
        return $pluginInfo ? $this->___callPlugins('getTable', func_get_args(), $pluginInfo) : parent::getTable($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getFlatTableName($storeId)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getFlatTableName');
        return $pluginInfo ? $this->___callPlugins('getFlatTableName', func_get_args(), $pluginInfo) : parent::getFlatTableName($storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($attributeCode)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getAttribute');
        return $pluginInfo ? $this->___callPlugins('getAttribute', func_get_args(), $pluginInfo) : parent::getAttribute($attributeCode);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAbandonedStoreFlatTables()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'deleteAbandonedStoreFlatTables');
        return $pluginInfo ? $this->___callPlugins('deleteAbandonedStoreFlatTables', func_get_args(), $pluginInfo) : parent::deleteAbandonedStoreFlatTables();
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
