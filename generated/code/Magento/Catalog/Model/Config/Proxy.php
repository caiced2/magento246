<?php
namespace Magento\Catalog\Model\Config;

/**
 * Proxy class for @see \Magento\Catalog\Model\Config
 */
class Proxy extends \Magento\Catalog\Model\Config implements \Magento\Framework\ObjectManager\NoninterceptableInterface
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;

    /**
     * Proxied instance name
     *
     * @var string
     */
    protected $_instanceName = null;

    /**
     * Proxied instance
     *
     * @var \Magento\Catalog\Model\Config
     */
    protected $_subject = null;

    /**
     * Instance shareability flag
     *
     * @var bool
     */
    protected $_isShared = null;

    /**
     * Proxy constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     * @param bool $shared
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, $instanceName = '\\Magento\\Catalog\\Model\\Config', $shared = true)
    {
        $this->_objectManager = $objectManager;
        $this->_instanceName = $instanceName;
        $this->_isShared = $shared;
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return ['_subject', '_isShared', '_instanceName'];
    }

    /**
     * Retrieve ObjectManager from global scope
     */
    public function __wakeup()
    {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    }

    /**
     * Clone proxied instance
     */
    public function __clone()
    {
        $this->_subject = clone $this->_getSubject();
    }

    /**
     * Get proxied instance
     *
     * @return \Magento\Catalog\Model\Config
     */
    protected function _getSubject()
    {
        if (!$this->_subject) {
            $this->_subject = true === $this->_isShared
                ? $this->_objectManager->get($this->_instanceName)
                : $this->_objectManager->create($this->_instanceName);
        }
        return $this->_subject;
    }

    /**
     * {@inheritdoc}
     */
    public function setStoreId($storeId)
    {
        return $this->_getSubject()->setStoreId($storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getStoreId()
    {
        return $this->_getSubject()->getStoreId();
    }

    /**
     * {@inheritdoc}
     */
    public function loadAttributeSets()
    {
        return $this->_getSubject()->loadAttributeSets();
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeSetName($entityTypeId, $id)
    {
        return $this->_getSubject()->getAttributeSetName($entityTypeId, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeSetId($entityTypeId, $name = null)
    {
        return $this->_getSubject()->getAttributeSetId($entityTypeId, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function loadAttributeGroups()
    {
        return $this->_getSubject()->loadAttributeGroups();
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeGroupName($attributeSetId, $id)
    {
        return $this->_getSubject()->getAttributeGroupName($attributeSetId, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeGroupId($attributeSetId, $name)
    {
        return $this->_getSubject()->getAttributeGroupId($attributeSetId, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function loadProductTypes()
    {
        return $this->_getSubject()->loadProductTypes();
    }

    /**
     * {@inheritdoc}
     */
    public function getProductTypeId($name)
    {
        return $this->_getSubject()->getProductTypeId($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getProductTypeName($id)
    {
        return $this->_getSubject()->getProductTypeName($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceOptionId($source, $value)
    {
        return $this->_getSubject()->getSourceOptionId($source, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getProductAttributes()
    {
        return $this->_getSubject()->getProductAttributes();
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributesUsedInProductListing()
    {
        return $this->_getSubject()->getAttributesUsedInProductListing();
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributesUsedForSortBy()
    {
        return $this->_getSubject()->getAttributesUsedForSortBy();
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeUsedForSortByArray()
    {
        return $this->_getSubject()->getAttributeUsedForSortByArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getProductListDefaultSortBy($store = null)
    {
        return $this->_getSubject()->getProductListDefaultSortBy($store);
    }

    /**
     * {@inheritdoc}
     */
    public function getCache()
    {
        return $this->_getSubject()->getCache();
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return $this->_getSubject()->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function isCacheEnabled()
    {
        return $this->_getSubject()->isCacheEnabled();
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityType($code)
    {
        return $this->_getSubject()->getEntityType($code);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes($entityType)
    {
        return $this->_getSubject()->getAttributes($entityType);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($entityType, $code)
    {
        return $this->_getSubject()->getAttribute($entityType, $code);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityAttributeCodes($entityType, $object = null)
    {
        return $this->_getSubject()->getEntityAttributeCodes($entityType, $object);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityAttributes($entityType, $object = null)
    {
        return $this->_getSubject()->getEntityAttributes($entityType, $object);
    }

    /**
     * {@inheritdoc}
     */
    public function importAttributesData($entityType, array $attributes)
    {
        return $this->_getSubject()->importAttributesData($entityType, $attributes);
    }
}
