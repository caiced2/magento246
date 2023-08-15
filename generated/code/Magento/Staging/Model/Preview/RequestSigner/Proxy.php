<?php
namespace Magento\Staging\Model\Preview\RequestSigner;

/**
 * Proxy class for @see \Magento\Staging\Model\Preview\RequestSigner
 */
class Proxy extends \Magento\Staging\Model\Preview\RequestSigner implements \Magento\Framework\ObjectManager\NoninterceptableInterface
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
     * @var \Magento\Staging\Model\Preview\RequestSigner
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
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, $instanceName = '\\Magento\\Staging\\Model\\Preview\\RequestSigner', $shared = true)
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
     * @return \Magento\Staging\Model\Preview\RequestSigner
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
    public function signUrl(string $url) : string
    {
        return $this->_getSubject()->signUrl($url);
    }

    /**
     * {@inheritdoc}
     */
    public function validateUrl(string $url) : bool
    {
        return $this->_getSubject()->validateUrl($url);
    }

    /**
     * {@inheritdoc}
     */
    public function generateSignatureParams(string $version, ?string $timestamp = null) : \Magento\Framework\DataObject
    {
        return $this->_getSubject()->generateSignatureParams($version, $timestamp);
    }
}
