<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Logging\Model;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Logging\Model\RemoteAddress\RemoteAddressInterface;
use Magento\Logging\Model\ResourceModel\Event as EventResourceModel;
use Magento\User\Model\UserFactory;

/**
 * Logging event model
 *
 * @api
 * @since 100.0.2
 */
class Event extends AbstractModel
{
    const RESULT_SUCCESS = 'success';

    const RESULT_FAILURE = 'failure';

    /**
     * @var UserFactory
     */
    protected $_userFactory;

    /**
     * Serializer Instance
     *
     * @var Json
     */
    private $json;

    /**
     * @var RemoteAddressFactory
     */
    private $remoteAddressFactory;

    /**
     * Construct
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param UserFactory $userFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     * @param Json|null $json
     * @param RemoteAddressFactory|null $remoteAddressFactory
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        UserFactory $userFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        Json $json = null,
        RemoteAddressFactory $remoteAddressFactory = null
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->_userFactory = $userFactory;
        $this->json = $json ?: ObjectManager::getInstance()->get(Json::class);
        $this->remoteAddressFactory = $remoteAddressFactory
            ?: ObjectManager::getInstance()->get(RemoteAddressFactory::class);
    }

    /**
     * Constructor
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(EventResourceModel::class);
    }

    /**
     * Set some data automatically before saving model
     *
     * @return $this
     */
    public function beforeSave()
    {
        $ipAddress = $this->remoteAddressFactory->create($this->getIp());
        $this->setIp($ipAddress->getLongFormat());

        $xForwardedIp = $this->remoteAddressFactory->create($this->getXForwardedIp());
        $this->setXForwardedIp($xForwardedIp->getLongFormat());

        $this->updateNewEventBeforeSave();

        $info = $this->prepareInfo($ipAddress, $xForwardedIp);
        $this->setInfo($this->json->serialize($info));
        return parent::beforeSave();
    }

    /**
     * @inheritDoc
     */
    public function afterLoad()
    {
        $remoteIp = $this->remoteAddressFactory->create($this->getIp());
        $this->setIp($remoteIp->getTextFormat());
        $xForwardedIp = $this->remoteAddressFactory->create($this->getXForwardedIp());
        $this->setXForwardedIp($xForwardedIp->getTextFormat());
        $this->readIpV6FromInfo('ip');
        $this->readIpV6FromInfo('x_forwarded_ip');

        return parent::afterLoad();
    }

    /**
     * Define if current event has event changes
     *
     * @return bool
     */
    public function hasChanges()
    {
        if ($this->getId()) {
            return (bool)$this->getResource()->getEventChangeIds($this->getId());
        }
        return false;
    }

    /**
     * Read IPs from info
     *
     * @param string $fieldName
     */
    private function readIpV6FromInfo(string $fieldName): void
    {
        $additionalInfoKey = $fieldName . '_v6';
        if ($this->getInfo()) {
            $info = $this->json->unserialize($this->getInfo());
            if (isset($info['additional'][$additionalInfoKey])) {
                $this->setData($fieldName, $info['additional'][$additionalInfoKey]);
                unset($info['additional'][$additionalInfoKey]);
                $this->setInfo($this->json->serialize($info));
            }
        }
    }

    /**
     * Update new object before save
     */
    private function updateNewEventBeforeSave(): void
    {
        if (!$this->getId()) {
            $this->setStatus($this->getIsSuccess() ? self::RESULT_SUCCESS : self::RESULT_FAILURE);
            if (!$this->getUser() && ($id = $this->getUserId())) {
                $this->setUser($this->_userFactory->create()->load($id)->getUserName());
            }
            if (!$this->hasTime()) {
                $this->setTime(time());
            }
        }
    }

    /**
     * Prepare Info and Additional Info
     *
     * @param RemoteAddressInterface $ipAddress
     * @param RemoteAddressInterface $xForwardedIp
     * @return array
     */
    private function prepareInfo(RemoteAddressInterface $ipAddress, RemoteAddressInterface $xForwardedIp): array
    {
        $info = [];
        $info['general'] = $this->getInfo();
        if ($this->getAdditionalInfo()) {
            $info['additional'] = $this->getAdditionalInfo();
        }
        if ($ipAddress->getLongFormat() === 0 && $ipAddress->getTextFormat()) {
            $info['additional']['ip_v6'] = $ipAddress->getTextFormat();
        }
        if ($xForwardedIp->getLongFormat() === 0 && $xForwardedIp->getTextFormat()) {
            $info['additional']['x_forwarded_ip_v6'] = $xForwardedIp->getTextFormat();
        }
        return $info;
    }
}
