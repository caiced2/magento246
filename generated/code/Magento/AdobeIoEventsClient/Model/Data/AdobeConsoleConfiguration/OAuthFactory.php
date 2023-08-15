<?php
namespace Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration;

/**
 * Factory class for @see \Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration\OAuth
 */
class OAuthFactory
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;

    /**
     * Instance name to create
     *
     * @var string
     */
    protected $_instanceName = null;

    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, $instanceName = '\\Magento\\AdobeIoEventsClient\\Model\\Data\\AdobeConsoleConfiguration\\OAuth')
    {
        $this->_objectManager = $objectManager;
        $this->_instanceName = $instanceName;
    }

    /**
     * Create class instance with specified parameters
     *
     * @param array $data
     * @return \Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration\OAuth
     */
    public function create(array $data = [])
    {
        return $this->_objectManager->create($this->_instanceName, $data);
    }
}
