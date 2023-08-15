<?php
namespace Magento\VersionsCms\Model\Hierarchy\Node;

/**
 * Interceptor class for @see \Magento\VersionsCms\Model\Hierarchy\Node
 */
class Interceptor extends \Magento\VersionsCms\Model\Hierarchy\Node implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\Model\Context $context, \Magento\Framework\Registry $registry, \Magento\VersionsCms\Helper\Hierarchy $cmsHierarchy, \Magento\VersionsCms\Model\Hierarchy\ConfigInterface $hierarchyConfig, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node $resource, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Store\Model\System\Store $systemStore, \Magento\VersionsCms\Model\Hierarchy\NodeFactory $nodeFactory, ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null, array $data = [], ?\Magento\Backend\Model\Validator\UrlKey\CompositeUrlKey $compositeUrlValidator = null, ?\Magento\Framework\App\Cache\TypeListInterface $appCache = null)
    {
        $this->___init();
        parent::__construct($context, $registry, $cmsHierarchy, $hierarchyConfig, $scopeConfig, $resource, $storeManager, $systemStore, $nodeFactory, $resourceCollection, $data, $compositeUrlValidator, $appCache);
    }

    /**
     * {@inheritdoc}
     */
    public function setScope($scope)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setScope');
        return $pluginInfo ? $this->___callPlugins('setScope', func_get_args(), $pluginInfo) : parent::setScope($scope);
    }

    /**
     * {@inheritdoc}
     */
    public function setScopeId($scopeId)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setScopeId');
        return $pluginInfo ? $this->___callPlugins('setScopeId', func_get_args(), $pluginInfo) : parent::setScopeId($scopeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getNodesData()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getNodesData');
        return $pluginInfo ? $this->___callPlugins('getNodesData', func_get_args(), $pluginInfo) : parent::getNodesData();
    }

    /**
     * {@inheritdoc}
     */
    public function getNodesCollection()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getNodesCollection');
        return $pluginInfo ? $this->___callPlugins('getNodesCollection', func_get_args(), $pluginInfo) : parent::getNodesCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function collectTree($data, $remove)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'collectTree');
        return $pluginInfo ? $this->___callPlugins('collectTree', func_get_args(), $pluginInfo) : parent::collectTree($data, $remove);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteByScope($scope, $scopeId)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'deleteByScope');
        return $pluginInfo ? $this->___callPlugins('deleteByScope', func_get_args(), $pluginInfo) : parent::deleteByScope($scope, $scopeId);
    }

    /**
     * {@inheritdoc}
     */
    public function setCollectActivePagesOnly($flag)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setCollectActivePagesOnly');
        return $pluginInfo ? $this->___callPlugins('setCollectActivePagesOnly', func_get_args(), $pluginInfo) : parent::setCollectActivePagesOnly($flag);
    }

    /**
     * {@inheritdoc}
     */
    public function setCollectIncludedPagesOnly($flag)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setCollectIncludedPagesOnly');
        return $pluginInfo ? $this->___callPlugins('setCollectIncludedPagesOnly', func_get_args(), $pluginInfo) : parent::setCollectIncludedPagesOnly($flag);
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getIdentifier');
        return $pluginInfo ? $this->___callPlugins('getIdentifier', func_get_args(), $pluginInfo) : parent::getIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function isUseDefaultIdentifier()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isUseDefaultIdentifier');
        return $pluginInfo ? $this->___callPlugins('isUseDefaultIdentifier', func_get_args(), $pluginInfo) : parent::isUseDefaultIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getLabel');
        return $pluginInfo ? $this->___callPlugins('getLabel', func_get_args(), $pluginInfo) : parent::getLabel();
    }

    /**
     * {@inheritdoc}
     */
    public function isUseDefaultLabel()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isUseDefaultLabel');
        return $pluginInfo ? $this->___callPlugins('isUseDefaultLabel', func_get_args(), $pluginInfo) : parent::isUseDefaultLabel();
    }

    /**
     * {@inheritdoc}
     */
    public function loadByRequestUrl($url)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'loadByRequestUrl');
        return $pluginInfo ? $this->___callPlugins('loadByRequestUrl', func_get_args(), $pluginInfo) : parent::loadByRequestUrl($url);
    }

    /**
     * {@inheritdoc}
     */
    public function loadFirstChildByParent($parentNodeId)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'loadFirstChildByParent');
        return $pluginInfo ? $this->___callPlugins('loadFirstChildByParent', func_get_args(), $pluginInfo) : parent::loadFirstChildByParent($parentNodeId);
    }

    /**
     * {@inheritdoc}
     */
    public function updateRewriteUrls(\Magento\Cms\Model\Page $page)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'updateRewriteUrls');
        return $pluginInfo ? $this->___callPlugins('updateRewriteUrls', func_get_args(), $pluginInfo) : parent::updateRewriteUrls($page);
    }

    /**
     * {@inheritdoc}
     */
    public function checkIdentifier($identifier, $storeId = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'checkIdentifier');
        return $pluginInfo ? $this->___callPlugins('checkIdentifier', func_get_args(), $pluginInfo) : parent::checkIdentifier($identifier, $storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetaNodeByType($type)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getMetaNodeByType');
        return $pluginInfo ? $this->___callPlugins('getMetaNodeByType', func_get_args(), $pluginInfo) : parent::getMetaNodeByType($type);
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl($store = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getUrl');
        return $pluginInfo ? $this->___callPlugins('getUrl', func_get_args(), $pluginInfo) : parent::getUrl($store);
    }

    /**
     * {@inheritdoc}
     */
    public function setTreeMaxDepth($depth)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setTreeMaxDepth');
        return $pluginInfo ? $this->___callPlugins('setTreeMaxDepth', func_get_args(), $pluginInfo) : parent::setTreeMaxDepth($depth);
    }

    /**
     * {@inheritdoc}
     */
    public function setTreeIsBrief($brief)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setTreeIsBrief');
        return $pluginInfo ? $this->___callPlugins('setTreeIsBrief', func_get_args(), $pluginInfo) : parent::setTreeIsBrief($brief);
    }

    /**
     * {@inheritdoc}
     */
    public function getTreeSlice($up = 0, $down = 0)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getTreeSlice');
        return $pluginInfo ? $this->___callPlugins('getTreeSlice', func_get_args(), $pluginInfo) : parent::getTreeSlice($up, $down);
    }

    /**
     * {@inheritdoc}
     */
    public function getParentNodeChildren()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getParentNodeChildren');
        return $pluginInfo ? $this->___callPlugins('getParentNodeChildren', func_get_args(), $pluginInfo) : parent::getParentNodeChildren();
    }

    /**
     * {@inheritdoc}
     */
    public function loadPageData()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'loadPageData');
        return $pluginInfo ? $this->___callPlugins('loadPageData', func_get_args(), $pluginInfo) : parent::loadPageData();
    }

    /**
     * {@inheritdoc}
     */
    public function appendPageToNodes($page, $nodes)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'appendPageToNodes');
        return $pluginInfo ? $this->___callPlugins('appendPageToNodes', func_get_args(), $pluginInfo) : parent::appendPageToNodes($page, $nodes);
    }

    /**
     * {@inheritdoc}
     */
    public function getTreeMetaData()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getTreeMetaData');
        return $pluginInfo ? $this->___callPlugins('getTreeMetaData', func_get_args(), $pluginInfo) : parent::getTreeMetaData();
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataPagerParams()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getMetadataPagerParams');
        return $pluginInfo ? $this->___callPlugins('getMetadataPagerParams', func_get_args(), $pluginInfo) : parent::getMetadataPagerParams();
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataContextMenuParams()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getMetadataContextMenuParams');
        return $pluginInfo ? $this->___callPlugins('getMetadataContextMenuParams', func_get_args(), $pluginInfo) : parent::getMetadataContextMenuParams();
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuLayout()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getMenuLayout');
        return $pluginInfo ? $this->___callPlugins('getMenuLayout', func_get_args(), $pluginInfo) : parent::getMenuLayout();
    }

    /**
     * {@inheritdoc}
     */
    public function afterSave()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'afterSave');
        return $pluginInfo ? $this->___callPlugins('afterSave', func_get_args(), $pluginInfo) : parent::afterSave();
    }

    /**
     * {@inheritdoc}
     */
    public function copyTo($scope, $scopeId)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'copyTo');
        return $pluginInfo ? $this->___callPlugins('copyTo', func_get_args(), $pluginInfo) : parent::copyTo($scope, $scopeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getIsInherited($soft = false)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getIsInherited');
        return $pluginInfo ? $this->___callPlugins('getIsInherited', func_get_args(), $pluginInfo) : parent::getIsInherited($soft);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeritage()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getHeritage');
        return $pluginInfo ? $this->___callPlugins('getHeritage', func_get_args(), $pluginInfo) : parent::getHeritage();
    }

    /**
     * {@inheritdoc}
     */
    public function getScope()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getScope');
        return $pluginInfo ? $this->___callPlugins('getScope', func_get_args(), $pluginInfo) : parent::getScope();
    }

    /**
     * {@inheritdoc}
     */
    public function getScopeId()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getScopeId');
        return $pluginInfo ? $this->___callPlugins('getScopeId', func_get_args(), $pluginInfo) : parent::getScopeId();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getId');
        return $pluginInfo ? $this->___callPlugins('getId', func_get_args(), $pluginInfo) : parent::getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getParentId()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getParentId');
        return $pluginInfo ? $this->___callPlugins('getParentId', func_get_args(), $pluginInfo) : parent::getParentId();
    }

    /**
     * {@inheritdoc}
     */
    public function getPageId()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getPageId');
        return $pluginInfo ? $this->___callPlugins('getPageId', func_get_args(), $pluginInfo) : parent::getPageId();
    }

    /**
     * {@inheritdoc}
     */
    public function getLevel()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getLevel');
        return $pluginInfo ? $this->___callPlugins('getLevel', func_get_args(), $pluginInfo) : parent::getLevel();
    }

    /**
     * {@inheritdoc}
     */
    public function getSortOrder()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getSortOrder');
        return $pluginInfo ? $this->___callPlugins('getSortOrder', func_get_args(), $pluginInfo) : parent::getSortOrder();
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestUrl()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getRequestUrl');
        return $pluginInfo ? $this->___callPlugins('getRequestUrl', func_get_args(), $pluginInfo) : parent::getRequestUrl();
    }

    /**
     * {@inheritdoc}
     */
    public function getXpath()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getXpath');
        return $pluginInfo ? $this->___callPlugins('getXpath', func_get_args(), $pluginInfo) : parent::getXpath();
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setId');
        return $pluginInfo ? $this->___callPlugins('setId', func_get_args(), $pluginInfo) : parent::setId($id);
    }

    /**
     * {@inheritdoc}
     */
    public function setParentId($parentId)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setParentId');
        return $pluginInfo ? $this->___callPlugins('setParentId', func_get_args(), $pluginInfo) : parent::setParentId($parentId);
    }

    /**
     * {@inheritdoc}
     */
    public function setPageId($pageId)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setPageId');
        return $pluginInfo ? $this->___callPlugins('setPageId', func_get_args(), $pluginInfo) : parent::setPageId($pageId);
    }

    /**
     * {@inheritdoc}
     */
    public function setIdentifier($identifier)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setIdentifier');
        return $pluginInfo ? $this->___callPlugins('setIdentifier', func_get_args(), $pluginInfo) : parent::setIdentifier($identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function setLabel($label)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setLabel');
        return $pluginInfo ? $this->___callPlugins('setLabel', func_get_args(), $pluginInfo) : parent::setLabel($label);
    }

    /**
     * {@inheritdoc}
     */
    public function setLevel($level)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setLevel');
        return $pluginInfo ? $this->___callPlugins('setLevel', func_get_args(), $pluginInfo) : parent::setLevel($level);
    }

    /**
     * {@inheritdoc}
     */
    public function setSortOrder($sortOrder)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setSortOrder');
        return $pluginInfo ? $this->___callPlugins('setSortOrder', func_get_args(), $pluginInfo) : parent::setSortOrder($sortOrder);
    }

    /**
     * {@inheritdoc}
     */
    public function setRequestUrl($requestUrl)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setRequestUrl');
        return $pluginInfo ? $this->___callPlugins('setRequestUrl', func_get_args(), $pluginInfo) : parent::setRequestUrl($requestUrl);
    }

    /**
     * {@inheritdoc}
     */
    public function setXpath($xpath)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setXpath');
        return $pluginInfo ? $this->___callPlugins('setXpath', func_get_args(), $pluginInfo) : parent::setXpath($xpath);
    }

    /**
     * {@inheritdoc}
     */
    public function setIdFieldName($name)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setIdFieldName');
        return $pluginInfo ? $this->___callPlugins('setIdFieldName', func_get_args(), $pluginInfo) : parent::setIdFieldName($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getIdFieldName()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getIdFieldName');
        return $pluginInfo ? $this->___callPlugins('getIdFieldName', func_get_args(), $pluginInfo) : parent::getIdFieldName();
    }

    /**
     * {@inheritdoc}
     */
    public function isDeleted($isDeleted = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isDeleted');
        return $pluginInfo ? $this->___callPlugins('isDeleted', func_get_args(), $pluginInfo) : parent::isDeleted($isDeleted);
    }

    /**
     * {@inheritdoc}
     */
    public function hasDataChanges()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'hasDataChanges');
        return $pluginInfo ? $this->___callPlugins('hasDataChanges', func_get_args(), $pluginInfo) : parent::hasDataChanges();
    }

    /**
     * {@inheritdoc}
     */
    public function setData($key, $value = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setData');
        return $pluginInfo ? $this->___callPlugins('setData', func_get_args(), $pluginInfo) : parent::setData($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function unsetData($key = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'unsetData');
        return $pluginInfo ? $this->___callPlugins('unsetData', func_get_args(), $pluginInfo) : parent::unsetData($key);
    }

    /**
     * {@inheritdoc}
     */
    public function setDataChanges($value)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setDataChanges');
        return $pluginInfo ? $this->___callPlugins('setDataChanges', func_get_args(), $pluginInfo) : parent::setDataChanges($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrigData($key = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getOrigData');
        return $pluginInfo ? $this->___callPlugins('getOrigData', func_get_args(), $pluginInfo) : parent::getOrigData($key);
    }

    /**
     * {@inheritdoc}
     */
    public function setOrigData($key = null, $data = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setOrigData');
        return $pluginInfo ? $this->___callPlugins('setOrigData', func_get_args(), $pluginInfo) : parent::setOrigData($key, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function dataHasChangedFor($field)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'dataHasChangedFor');
        return $pluginInfo ? $this->___callPlugins('dataHasChangedFor', func_get_args(), $pluginInfo) : parent::dataHasChangedFor($field);
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceName()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getResourceName');
        return $pluginInfo ? $this->___callPlugins('getResourceName', func_get_args(), $pluginInfo) : parent::getResourceName();
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceCollection()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getResourceCollection');
        return $pluginInfo ? $this->___callPlugins('getResourceCollection', func_get_args(), $pluginInfo) : parent::getResourceCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getCollection');
        return $pluginInfo ? $this->___callPlugins('getCollection', func_get_args(), $pluginInfo) : parent::getCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function load($modelId, $field = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'load');
        return $pluginInfo ? $this->___callPlugins('load', func_get_args(), $pluginInfo) : parent::load($modelId, $field);
    }

    /**
     * {@inheritdoc}
     */
    public function beforeLoad($identifier, $field = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'beforeLoad');
        return $pluginInfo ? $this->___callPlugins('beforeLoad', func_get_args(), $pluginInfo) : parent::beforeLoad($identifier, $field);
    }

    /**
     * {@inheritdoc}
     */
    public function afterLoad()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'afterLoad');
        return $pluginInfo ? $this->___callPlugins('afterLoad', func_get_args(), $pluginInfo) : parent::afterLoad();
    }

    /**
     * {@inheritdoc}
     */
    public function isSaveAllowed()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isSaveAllowed');
        return $pluginInfo ? $this->___callPlugins('isSaveAllowed', func_get_args(), $pluginInfo) : parent::isSaveAllowed();
    }

    /**
     * {@inheritdoc}
     */
    public function setHasDataChanges($flag)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setHasDataChanges');
        return $pluginInfo ? $this->___callPlugins('setHasDataChanges', func_get_args(), $pluginInfo) : parent::setHasDataChanges($flag);
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'save');
        return $pluginInfo ? $this->___callPlugins('save', func_get_args(), $pluginInfo) : parent::save();
    }

    /**
     * {@inheritdoc}
     */
    public function afterCommitCallback()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'afterCommitCallback');
        return $pluginInfo ? $this->___callPlugins('afterCommitCallback', func_get_args(), $pluginInfo) : parent::afterCommitCallback();
    }

    /**
     * {@inheritdoc}
     */
    public function isObjectNew($flag = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isObjectNew');
        return $pluginInfo ? $this->___callPlugins('isObjectNew', func_get_args(), $pluginInfo) : parent::isObjectNew($flag);
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'beforeSave');
        return $pluginInfo ? $this->___callPlugins('beforeSave', func_get_args(), $pluginInfo) : parent::beforeSave();
    }

    /**
     * {@inheritdoc}
     */
    public function validateBeforeSave()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'validateBeforeSave');
        return $pluginInfo ? $this->___callPlugins('validateBeforeSave', func_get_args(), $pluginInfo) : parent::validateBeforeSave();
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheTags()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getCacheTags');
        return $pluginInfo ? $this->___callPlugins('getCacheTags', func_get_args(), $pluginInfo) : parent::getCacheTags();
    }

    /**
     * {@inheritdoc}
     */
    public function cleanModelCache()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'cleanModelCache');
        return $pluginInfo ? $this->___callPlugins('cleanModelCache', func_get_args(), $pluginInfo) : parent::cleanModelCache();
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'delete');
        return $pluginInfo ? $this->___callPlugins('delete', func_get_args(), $pluginInfo) : parent::delete();
    }

    /**
     * {@inheritdoc}
     */
    public function beforeDelete()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'beforeDelete');
        return $pluginInfo ? $this->___callPlugins('beforeDelete', func_get_args(), $pluginInfo) : parent::beforeDelete();
    }

    /**
     * {@inheritdoc}
     */
    public function afterDelete()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'afterDelete');
        return $pluginInfo ? $this->___callPlugins('afterDelete', func_get_args(), $pluginInfo) : parent::afterDelete();
    }

    /**
     * {@inheritdoc}
     */
    public function afterDeleteCommit()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'afterDeleteCommit');
        return $pluginInfo ? $this->___callPlugins('afterDeleteCommit', func_get_args(), $pluginInfo) : parent::afterDeleteCommit();
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getResource');
        return $pluginInfo ? $this->___callPlugins('getResource', func_get_args(), $pluginInfo) : parent::getResource();
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityId()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getEntityId');
        return $pluginInfo ? $this->___callPlugins('getEntityId', func_get_args(), $pluginInfo) : parent::getEntityId();
    }

    /**
     * {@inheritdoc}
     */
    public function setEntityId($entityId)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setEntityId');
        return $pluginInfo ? $this->___callPlugins('setEntityId', func_get_args(), $pluginInfo) : parent::setEntityId($entityId);
    }

    /**
     * {@inheritdoc}
     */
    public function clearInstance()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'clearInstance');
        return $pluginInfo ? $this->___callPlugins('clearInstance', func_get_args(), $pluginInfo) : parent::clearInstance();
    }

    /**
     * {@inheritdoc}
     */
    public function getStoredData()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getStoredData');
        return $pluginInfo ? $this->___callPlugins('getStoredData', func_get_args(), $pluginInfo) : parent::getStoredData();
    }

    /**
     * {@inheritdoc}
     */
    public function getEventPrefix()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getEventPrefix');
        return $pluginInfo ? $this->___callPlugins('getEventPrefix', func_get_args(), $pluginInfo) : parent::getEventPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function addData(array $arr)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'addData');
        return $pluginInfo ? $this->___callPlugins('addData', func_get_args(), $pluginInfo) : parent::addData($arr);
    }

    /**
     * {@inheritdoc}
     */
    public function getData($key = '', $index = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getData');
        return $pluginInfo ? $this->___callPlugins('getData', func_get_args(), $pluginInfo) : parent::getData($key, $index);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataByPath($path)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getDataByPath');
        return $pluginInfo ? $this->___callPlugins('getDataByPath', func_get_args(), $pluginInfo) : parent::getDataByPath($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataByKey($key)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getDataByKey');
        return $pluginInfo ? $this->___callPlugins('getDataByKey', func_get_args(), $pluginInfo) : parent::getDataByKey($key);
    }

    /**
     * {@inheritdoc}
     */
    public function setDataUsingMethod($key, $args = [])
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setDataUsingMethod');
        return $pluginInfo ? $this->___callPlugins('setDataUsingMethod', func_get_args(), $pluginInfo) : parent::setDataUsingMethod($key, $args);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataUsingMethod($key, $args = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getDataUsingMethod');
        return $pluginInfo ? $this->___callPlugins('getDataUsingMethod', func_get_args(), $pluginInfo) : parent::getDataUsingMethod($key, $args);
    }

    /**
     * {@inheritdoc}
     */
    public function hasData($key = '')
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'hasData');
        return $pluginInfo ? $this->___callPlugins('hasData', func_get_args(), $pluginInfo) : parent::hasData($key);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(array $keys = [])
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'toArray');
        return $pluginInfo ? $this->___callPlugins('toArray', func_get_args(), $pluginInfo) : parent::toArray($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToArray(array $keys = [])
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'convertToArray');
        return $pluginInfo ? $this->___callPlugins('convertToArray', func_get_args(), $pluginInfo) : parent::convertToArray($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function toXml(array $keys = [], $rootName = 'item', $addOpenTag = false, $addCdata = true)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'toXml');
        return $pluginInfo ? $this->___callPlugins('toXml', func_get_args(), $pluginInfo) : parent::toXml($keys, $rootName, $addOpenTag, $addCdata);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToXml(array $arrAttributes = [], $rootName = 'item', $addOpenTag = false, $addCdata = true)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'convertToXml');
        return $pluginInfo ? $this->___callPlugins('convertToXml', func_get_args(), $pluginInfo) : parent::convertToXml($arrAttributes, $rootName, $addOpenTag, $addCdata);
    }

    /**
     * {@inheritdoc}
     */
    public function toJson(array $keys = [])
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'toJson');
        return $pluginInfo ? $this->___callPlugins('toJson', func_get_args(), $pluginInfo) : parent::toJson($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToJson(array $keys = [])
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'convertToJson');
        return $pluginInfo ? $this->___callPlugins('convertToJson', func_get_args(), $pluginInfo) : parent::convertToJson($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function toString($format = '')
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'toString');
        return $pluginInfo ? $this->___callPlugins('toString', func_get_args(), $pluginInfo) : parent::toString($format);
    }

    /**
     * {@inheritdoc}
     */
    public function __call($method, $args)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, '__call');
        return $pluginInfo ? $this->___callPlugins('__call', func_get_args(), $pluginInfo) : parent::__call($method, $args);
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isEmpty');
        return $pluginInfo ? $this->___callPlugins('isEmpty', func_get_args(), $pluginInfo) : parent::isEmpty();
    }

    /**
     * {@inheritdoc}
     */
    public function serialize($keys = [], $valueSeparator = '=', $fieldSeparator = ' ', $quote = '"')
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'serialize');
        return $pluginInfo ? $this->___callPlugins('serialize', func_get_args(), $pluginInfo) : parent::serialize($keys, $valueSeparator, $fieldSeparator, $quote);
    }

    /**
     * {@inheritdoc}
     */
    public function debug($data = null, &$objects = [])
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'debug');
        return $pluginInfo ? $this->___callPlugins('debug', func_get_args(), $pluginInfo) : parent::debug($data, $objects);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'offsetSet');
        return $pluginInfo ? $this->___callPlugins('offsetSet', func_get_args(), $pluginInfo) : parent::offsetSet($offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'offsetExists');
        return $pluginInfo ? $this->___callPlugins('offsetExists', func_get_args(), $pluginInfo) : parent::offsetExists($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'offsetUnset');
        return $pluginInfo ? $this->___callPlugins('offsetUnset', func_get_args(), $pluginInfo) : parent::offsetUnset($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'offsetGet');
        return $pluginInfo ? $this->___callPlugins('offsetGet', func_get_args(), $pluginInfo) : parent::offsetGet($offset);
    }
}
