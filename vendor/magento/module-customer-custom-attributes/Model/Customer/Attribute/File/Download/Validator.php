<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Model\Customer\Attribute\File\Download;

use Magento\Customer\Model\Metadata\CustomerMetadata;
use Magento\CustomerCustomAttributes\Model\Customer\FileDownloadValidator;
use Magento\CustomerCustomAttributes\Model\Customer\FileDownloadValidatorFactory;
use Magento\Framework\App\ObjectManager;

/**
 * Class Validator validates if user can have access to requested file
 */
class Validator
{
    /**
     * @var CustomerMetadata
     */
    private $customerMetaData;

    /**
     * @var FileDownloadValidatorFactory
     */
    private $fileDownloadValidatorFactory;

    /**
     * @param CustomerMetadata $customerMetaData
     * @param FileDownloadValidatorFactory|null $fileDownloadValidatorFactory
     */
    public function __construct(
        CustomerMetadata $customerMetaData,
        ?FileDownloadValidatorFactory $fileDownloadValidatorFactory = null
    ) {
        $this->customerMetaData = $customerMetaData;
        $this->fileDownloadValidatorFactory = $fileDownloadValidatorFactory ??
            ObjectManager::getInstance()->get(FileDownloadValidatorFactory::class);
    }

    /**
     * Check if customer can download file
     *
     * @param string $fileName
     * @param array $customAttributes
     * @return bool
     */
    public function canDownloadFile(string $fileName, array $customAttributes) : bool
    {
        /**
         * @var FileDownloadValidator $validator
         */
        $validator = $this->fileDownloadValidatorFactory->create(
            [
                'metadata' => $this->customerMetaData,
                'entityTypeCode' => CustomerMetadata::ENTITY_TYPE_CUSTOMER,
            ]
        );
        return $validator->canDownloadFile(
            $fileName,
            $customAttributes,
        );
    }
}
