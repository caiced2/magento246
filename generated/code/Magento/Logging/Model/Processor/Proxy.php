<?php
namespace Magento\Logging\Model\Processor;

/**
 * Proxy class for @see \Magento\Logging\Model\Processor
 */
class Proxy extends \Magento\Logging\Model\Processor implements \Magento\Framework\ObjectManager\NoninterceptableInterface
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
     * @var \Magento\Logging\Model\Processor
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
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, $instanceName = '\\Magento\\Logging\\Model\\Processor', $shared = true)
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
     * @return \Magento\Logging\Model\Processor
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
    public function initAction($fullActionName, $actionName)
    {
        return $this->_getSubject()->initAction($fullActionName, $actionName);
    }

    /**
     * {@inheritdoc}
     */
    public function modelActionAfter($model, $action)
    {
        return $this->_getSubject()->modelActionAfter($model, $action);
    }

    /**
     * {@inheritdoc}
     */
    public function logAction()
    {
        return $this->_getSubject()->logAction();
    }

    /**
     * {@inheritdoc}
     */
    public function logDeniedAction()
    {
        return $this->_getSubject()->logDeniedAction();
    }

    /**
     * {@inheritdoc}
     */
    public function collectId($model)
    {
        return $this->_getSubject()->collectId($model);
    }

    /**
     * {@inheritdoc}
     */
    public function getCollectedIds()
    {
        return $this->_getSubject()->getCollectedIds();
    }

    /**
     * {@inheritdoc}
     */
    public function collectAdditionalData($model, array $attributes)
    {
        return $this->_getSubject()->collectAdditionalData($model, $attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function getCollectedAdditionalData()
    {
        return $this->_getSubject()->getCollectedAdditionalData();
    }

    /**
     * {@inheritdoc}
     */
    public function addEventChanges($eventChange)
    {
        return $this->_getSubject()->addEventChanges($eventChange);
    }

    /**
     * {@inheritdoc}
     */
    public function createChanges($name, $original, $result)
    {
        return $this->_getSubject()->createChanges($name, $original, $result);
    }
}
