<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Model\Entity\PeriodSync;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class Consumer
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var EntitySynchronizer
     */
    private $entitySynchronizer;

    /**
     * @param LoggerInterface $logger
     * @param SerializerInterface $serializer
     * @param EntityManager $entityManager
     * @param EntitySynchronizer $entitySynchronizer
     */
    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        EntityManager $entityManager,
        EntitySynchronizer $entitySynchronizer
    ) {
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->entitySynchronizer = $entitySynchronizer;
    }

    /**
     * Process staging update synchronization.
     *
     * @param OperationInterface $operation
     * @return void
     */
    public function process(OperationInterface $operation): void
    {
        try {
            $data = $this->serializer->unserialize($operation->getSerializedData());
            $this->entitySynchronizer->execute((int) $data['update_id']);
            $operation->setStatus(OperationInterface::STATUS_TYPE_COMPLETE);
            $operation->setResultMessage(null);
        } catch (LocalizedException $e) {
            $operation->setStatus(OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED);
            $operation->setErrorCode($e->getCode());
            $operation->setResultMessage($e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->critical($e);
            $operation->setStatus(OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED);
            $operation->setErrorCode($e->getCode());
            $operation->setResultMessage(
                __('Sorry, something went wrong during update synchronization. Please see log for details.')
            );
        } finally {
            $this->entityManager->save($operation);
        }
    }
}
