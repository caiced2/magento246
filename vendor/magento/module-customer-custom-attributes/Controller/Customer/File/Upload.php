<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Controller\Customer\File;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Model\FileUploaderFactory;
use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\FileProcessorFactory;
use Magento\Framework\App\RequestInterface;
use Magento\CustomerCustomAttributes\Controller\AbstractUploadFile;

/**
 * Class for uploading files for customer custom attributes
 */
class Upload extends AbstractUploadFile
{
    /**
     * @param FileUploaderFactory $fileUploaderFactory
     * @param LoggerInterface $logger
     * @param FileProcessorFactory $fileProcessorFactory
     * @param RequestInterface $request
     * @param ResultFactory $resultFactory
     * @param CustomerMetadataInterface $customerMetadataService
     */
    public function __construct(
        FileUploaderFactory $fileUploaderFactory,
        LoggerInterface $logger,
        FileProcessorFactory $fileProcessorFactory,
        RequestInterface $request,
        ResultFactory $resultFactory,
        CustomerMetadataInterface $customerMetadataService
    ) {
        parent::__construct(
            $fileUploaderFactory,
            $logger,
            $fileProcessorFactory,
            $request,
            $resultFactory,
            $customerMetadataService
        );
    }

    /**
     * Returns entity type of customer
     *
     * @return string
     */
    protected function getEntityType(): string
    {
        return CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER;
    }
}
