<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Model\Entity\PeriodSync;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;

class Scheduler
{
    private const TOPIC_NAME = 'staging.synchronize_entity_period';

    /**
     * @var BulkManagementInterface
     */
    private $bulkManagement;

    /**
     * @var IdentityGeneratorInterface
     */
    private $identityGenerator;

    /**
     * @var OperationInterfaceFactory
     */
    private $operationFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param BulkManagementInterface $bulkManagement
     * @param IdentityGeneratorInterface $identityGenerator
     * @param OperationInterfaceFactory $operationFactory
     * @param SerializerInterface $serializer
     */
    public function __construct(
        BulkManagementInterface $bulkManagement,
        IdentityGeneratorInterface $identityGenerator,
        OperationInterfaceFactory $operationFactory,
        SerializerInterface $serializer
    ) {
        $this->bulkManagement = $bulkManagement;
        $this->identityGenerator = $identityGenerator;
        $this->operationFactory = $operationFactory;
        $this->serializer = $serializer;
    }

    /**
     * Schedule staging updates synchronization.
     *
     * @param array $updateIds
     * @return void
     * @throws LocalizedException
     */
    public function execute(array $updateIds): void
    {
        $bulkUuid = $this->identityGenerator->generateId();
        $bulkDescription = __('Synchronize staged entities periods');

        $operations = [];
        foreach ($updateIds as $updateId) {
            $data = [
                'data' => [
                    'bulk_uuid' => $bulkUuid,
                    'topic_name' => self::TOPIC_NAME,
                    'serialized_data' => $this->serializer->serialize(['update_id' => $updateId]),
                    'status' => OperationInterface::STATUS_TYPE_OPEN,
                ],
            ];
            $operation = $this->operationFactory->create($data);
            $operations[] = $operation;
        }

        $result = $this->bulkManagement->scheduleBulk($bulkUuid, $operations, $bulkDescription);
        if (!$result) {
            throw new LocalizedException(
                __('Something went wrong while scheduling operations.')
            );
        }
    }
}
