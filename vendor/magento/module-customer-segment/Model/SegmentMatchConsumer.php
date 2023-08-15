<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Model;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Framework\DB\Adapter\ConnectionException;
use Magento\Framework\DB\Adapter\DeadlockException;
use Magento\Framework\DB\Adapter\LockWaitException;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\TemporaryStateExceptionInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\CustomerSegment\Model\ResourceModel\Segment as SegmentResourceModel;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Magento\CustomerSegment\Model\SegmentFactory;

/**
 * Class for processing matched customers segment from messages queue.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SegmentMatchConsumer
{
    /** @var SegmentResourceModel */
    private $resourceModel;

    /**  @var LoggerInterface */
    private $logger;

    /** @var Json */
    private $jsonHelper;

    /** @var SegmentFactory */
    private $segmentFactory;

    /** @var EntityManager */
    private $entityManager;

    /**
     * @param LoggerInterface $logger
     * @param Json $jsonHelper
     * @param SegmentFactory $segmentFactory
     * @param SegmentResourceModel $resourceModel
     * @param EntityManager $entityManager
     */
    public function __construct(
        LoggerInterface $logger,
        Json $jsonHelper,
        SegmentFactory $segmentFactory,
        SegmentResourceModel $resourceModel,
        EntityManager $entityManager
    ) {
        $this->logger = $logger;
        $this->jsonHelper = $jsonHelper;
        $this->segmentFactory = $segmentFactory;
        $this->resourceModel = $resourceModel;
        $this->entityManager = $entityManager;
    }

    /**
     * Processing operation for match customers segment
     *
     * @param OperationInterface $operation
     * @return void
     * @throws \Exception
     */
    public function process(OperationInterface $operation)
    {
        $status = OperationInterface::STATUS_TYPE_COMPLETE;
        $errorCode = null;
        $message = __('Customer segment updated successfully.');
        $serializedData = $operation->getSerializedData();
        $unserializedData = $this->jsonHelper->unserialize($serializedData);
        try {
            $this->execute($unserializedData);
        } catch (\Zend_Db_Adapter_Exception  $e) {
            $this->logger->critical($e->getMessage());
            if ($e instanceof LockWaitException
                || $e instanceof DeadlockException
                || $e instanceof ConnectionException
            ) {
                $status = OperationInterface::STATUS_TYPE_RETRIABLY_FAILED;
                $errorCode = $e->getCode();
                $message = __($e->getMessage());
            } else {
                $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
                $errorCode = $e->getCode();
                $message = __(
                    'Sorry, something went wrong during customer segment update. Please see log for details.'
                );
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->critical($e->getMessage());
            $status = ($e instanceof TemporaryStateExceptionInterface)
                ? OperationInterface::STATUS_TYPE_RETRIABLY_FAILED
                : OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            unset($unserializedData['entity_link']);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = __('Sorry, something went wrong during customer segment update. Please see log for details.');
        }
        $operation->setStatus($status ?? OperationInterface::STATUS_TYPE_COMPLETE)
            ->setErrorCode($errorCode ?? null)
            ->setResultMessage($message ?? null);

        $this->entityManager->save($operation);
    }

    /**
     * Aggregate matched customers
     *
     * @param array $data
     * @return void
     * @throws \Exception
     */
    private function execute(array $data): void
    {
        $segment = $this->segmentFactory->create()
            ->load($data['entity_id']);
        $this->resourceModel->aggregateMatchedCustomers($segment);
    }
}
