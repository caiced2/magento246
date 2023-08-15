<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Model\ResourceModel;

use Magento\Framework\Math\Random;
use Magento\Framework\Model\AbstractModel;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Helper\Data as RmaData;
use Magento\Rma\Model\Spi\RmaResourceInterface;
use Magento\Rma\Model\Rma\Create;
use Magento\Framework\App\ObjectManager;
use Magento\Rma\Model\Spi\CommentResourceInterface;
use Magento\Rma\Model\Spi\TrackResourceInterface;

/**
 * RMA entity resource model
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Rma extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb implements RmaResourceInterface
{
    /**
     * Rma grid factory
     *
     * @var \Magento\Rma\Model\GridFactory
     */
    protected $rmaGridFactory;

    /**
     * Eav configuration model
     *
     * @var \Magento\SalesSequence\Model\Manager
     */
    protected $sequenceManager;

    /**
     * @var Create
     */
    private $rmaCreate;

    /**
     * @var CommentResourceInterface
     */
    private $commentResource;

    /**
     * @var TrackResourceInterface
     */
    private $trackResource;

    /**
     * @var RmaData
     */
    private $rmaData;

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Rma\Model\GridFactory $rmaGridFactory
     * @param \Magento\SalesSequence\Model\Manager $sequenceManager
     * @param string|null $connectionName
     * @param Create|null $rmaCreate
     * @param CommentResourceInterface|null $commentResource
     * @param TrackResourceInterface|null $trackResource
     * @param RmaData|null $rmaData
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Rma\Model\GridFactory $rmaGridFactory,
        \Magento\SalesSequence\Model\Manager $sequenceManager,
        $connectionName = null,
        Create $rmaCreate = null,
        CommentResourceInterface $commentResource = null,
        TrackResourceInterface $trackResource = null,
        RmaData $rmaData = null
    ) {
        $this->rmaGridFactory = $rmaGridFactory;
        $this->sequenceManager = $sequenceManager;
        $this->rmaCreate = $rmaCreate ?: ObjectManager::getInstance()->get(Create::class);
        $this->commentResource = $commentResource ?:
            ObjectManager::getInstance()->get(CommentResourceInterface::class);
        $this->trackResource = $trackResource ?: ObjectManager::getInstance()->get(TrackResourceInterface::class);
        $this->rmaData = $rmaData ?: ObjectManager::getInstance()->get(RmaData::class);
        parent::__construct($context, $connectionName);
    }

    /**
     * Internal constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_rma', 'entity_id');
    }

    /**
     * Perform actions after object save
     *
     * @param AbstractModel|\Magento\Framework\DataObject $object
     * @return $this
     */
    protected function _afterSave(AbstractModel $object)
    {
        parent::_afterSave($object);

        $this->saveRmaGrid($object);
        $this->saveComments($object);
        $this->saveTracks($object);
        $this->saveRmaItems($object);

        return $this;
    }

    /**
     * Perform actions before object save
     *
     * @param AbstractModel|\Magento\Framework\DataObject $object
     * @return $this
     */
    protected function _beforeSave(AbstractModel $object)
    {
        parent::_beforeSave($object);
        /** @var \Magento\Rma\Model\Rma $object */
        if (!$object->getIncrementId()) {
            $incrementId = $this->sequenceManager->getSequence('rma_item', $object->getStoreId())->getNextValue();
            $object->setIncrementId($incrementId);
        }
        if (!$object->getIsUpdate()) {
            $object->setData(
                'protect_code',
                substr(
                    sha1(
                        uniqid(
                            (string)Random::getRandomNumber(),
                            true
                        ) . ':' . microtime(true)
                    ),
                    5,
                    6
                )
            );
        }

        if (!$object->getCustomerId() && $object->getOrderId()) {
            $order = $object->getOrder();
            $object->setCustomerId($order->getCustomerId());
        }

        return $this;
    }

    /**
     * Save comments for RMA.
     *
     * @param AbstractModel $rma
     * @return void
     */
    private function saveComments(AbstractModel $rma)
    {
        foreach ($rma->getComments() as $comment) {
            $comment->setRmaEntityId($rma->getEntityId());
            $this->commentResource->save($comment);
        }
    }

    /**
     * Save tracks for RMA.
     *
     * @param AbstractModel $rma
     * @return void
     */
    private function saveTracks(AbstractModel $rma)
    {
        foreach ($rma->getTracks() as $track) {
            $track->setRmaEntityId($rma->getEntityId());
            $this->trackResource->save($track);
        }
    }

    /**
     * Save RMA grid.
     *
     * @param RmaInterface $rma
     * @return void
     * @throws \Exception
     */
    private function saveRmaGrid(RmaInterface $rma): void
    {
        /** @var \Magento\Rma\Model\Grid $gridModel */
        $gridModel = $this->rmaGridFactory->create();
        $order = $rma->getOrder();

        $gridModel->addData($rma->getData());
        $gridModel->setOrderDate($order->getCreatedAt());
        $gridModel->setCustomerName($order->getCustomerName());
        $gridModel->save();
    }

    /**
     * Save RMA items.
     *
     * @param RmaInterface $rma
     * @return void
     */
    private function saveRmaItems(RmaInterface $rma): void
    {
        $order = $rma->getOrder();
        $itemsCollection = $rma->getItems();
        if (is_array($itemsCollection)) {
            foreach ($itemsCollection as $item) {
                /** @var \Magento\Sales\Model\Order\Item $orderItem */
                $orderItem = $order->getItemById($item->getOrderItemId());
                $item->setProductName($orderItem->getName());
                $item->setProductSku($orderItem->getSku());
                $item->setProductAdminName($this->rmaData->getAdminProductName($orderItem));
                $item->setProductAdminSku($this->rmaData->getAdminProductSku($orderItem));
                $item->save();
            }
        }
    }
}
