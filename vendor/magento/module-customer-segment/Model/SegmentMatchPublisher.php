<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Model;

use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Publish matched customers segment to messages queue
 */
class SegmentMatchPublisher
{
    /** @var BulkManagementInterface */
    private $bulkManagement;

    /** @var OperationInterfaceFactory */
    private $operationFactory;

    /** @var IdentityGeneratorInterface */
    private $identityService;

    /** @var UrlInterface */
    private $urlBuilder;

    /** @var UserContextInterface */
    private $userContext;

    /** @var Json */
    private $jsonHelper;

    /**
     * @param BulkManagementInterface $bulkManagement
     * @param OperationInterfaceFactory $operationFactory
     * @param IdentityGeneratorInterface $identityService
     * @param UserContextInterface $userContextInterface
     * @param UrlInterface $urlBuilder
     * @param Json $jsonHelper
     */
    public function __construct(
        BulkManagementInterface $bulkManagement,
        OperationInterfaceFactory $operationFactory,
        IdentityGeneratorInterface $identityService,
        UserContextInterface $userContextInterface,
        UrlInterface $urlBuilder,
        Json $jsonHelper
    ) {
        $this->bulkManagement = $bulkManagement;
        $this->operationFactory = $operationFactory;
        $this->identityService = $identityService;
        $this->userContext = $userContextInterface;
        $this->urlBuilder = $urlBuilder;
        $this->jsonHelper = $jsonHelper;
    }

    /**
     * Schedule bulk operation
     *
     * @param Segment $model
     * @return bool
     * @throws LocalizedException
     */
    public function execute(Segment $model): bool
    {
        $bulkUuid = $this->identityService->generateId();
        $bulkDescription = __('Customer Segment: %1', $model->getId());
        $serializedData = [
            'entity_id' => $model->getId(),
            'entity_link' => $this->urlBuilder->getUrl('customersegment/*/edit', ['id' => $model->getId()]),
            'meta_information' => 'Update matched customers segment',
        ];
        $data = [
            'data' => [
                'bulk_uuid' => $bulkUuid,
                'topic_name' => 'customer_segment.match',
                'serialized_data' => $this->jsonHelper->serialize($serializedData),
                'status' => OperationInterface::STATUS_TYPE_OPEN,
            ]
        ];
        /** @var OperationInterface $operation */
        $operation = $this->operationFactory->create($data);
        $userId = $this->userContext->getUserId();

        return $this->bulkManagement->scheduleBulk($bulkUuid, [$operation], $bulkDescription, $userId);
    }
}
