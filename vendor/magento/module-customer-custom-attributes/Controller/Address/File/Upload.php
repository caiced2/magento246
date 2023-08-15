<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Controller\Address\File;

use Magento\Customer\Model\FileUploaderFactory;
use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\FileProcessorFactory;
use Magento\Framework\App\RequestInterface;
use Magento\CustomerCustomAttributes\Controller\AbstractUploadFile;
use Magento\Customer\Api\AddressMetadataInterface;

/**
 * Class for uploading files for customer custom address attributes
 */
class Upload extends AbstractUploadFile
{
    /**
     * @param FileUploaderFactory $fileUploaderFactory
     * @param LoggerInterface $logger
     * @param FileProcessorFactory $fileProcessorFactory
     * @param RequestInterface $request
     * @param ResultFactory $resultFactory
     * @param AddressMetadataInterface $addressMetadataService
     */
    public function __construct(
        FileUploaderFactory $fileUploaderFactory,
        LoggerInterface $logger,
        FileProcessorFactory $fileProcessorFactory,
        RequestInterface $request,
        ResultFactory $resultFactory,
        AddressMetadataInterface $addressMetadataService
    ) {
        parent::__construct(
            $fileUploaderFactory,
            $logger,
            $fileProcessorFactory,
            $request,
            $resultFactory,
            $addressMetadataService
        );
    }

    /**
     * Returns entity type of address
     *
     * @return string
     */
    protected function getEntityType(): string
    {
        return AddressMetadataInterface::ENTITY_TYPE_ADDRESS;
    }
}
