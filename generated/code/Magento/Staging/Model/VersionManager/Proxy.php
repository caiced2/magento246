<?php
namespace Magento\Staging\Model\VersionManager;

/**
 * Proxy class for @see \Magento\Staging\Model\VersionManager
 */
class Proxy extends \Magento\Staging\Model\VersionManager implements \Magento\Framework\ObjectManager\NoninterceptableInterface
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
     * @var \Magento\Staging\Model\VersionManager
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
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, $instanceName = '\\Magento\\Staging\\Model\\VersionManager', $shared = true)
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
     * @return \Magento\Staging\Model\VersionManager
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
    public function setCurrentVersionId($versionId)
    {
        return $this->_getSubject()->setCurrentVersionId($versionId);
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return $this->_getSubject()->getVersion();
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestedTimestamp()
    {
        return $this->_getSubject()->getRequestedTimestamp();
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentVersion()
    {
        return $this->_getSubject()->getCurrentVersion();
    }

    /**
     * {@inheritdoc}
     */
    public function isPreviewVersion()
    {
        return $this->_getSubject()->isPreviewVersion();
    }
}
