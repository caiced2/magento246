<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Model\Customer\Address\Attribute\File\Download;

use Magento\CustomerCustomAttributes\Model\Customer\FileDownloadValidator;
use Magento\CustomerCustomAttributes\Model\Customer\FileDownloadValidatorFactory;
use Magento\Customer\Api\AddressMetadataInterface;

/**
 * Class Validator validates if user can have access to requested file
 */
class Validator
{
    /**
     * @var AddressMetadataInterface
     */
    private $addressMetadata;

    /**
     * @var FileDownloadValidatorFactory
     */
    private $fileDownloadValidatorFactory;

    /**
     * @param AddressMetadataInterface $addressMetadata
     * @param FileDownloadValidatorFactory $fileDownloadValidatorFactory
     */
    public function __construct(
        AddressMetadataInterface $addressMetadata,
        FileDownloadValidatorFactory $fileDownloadValidatorFactory
    ) {
        $this->addressMetadata = $addressMetadata;
        $this->fileDownloadValidatorFactory = $fileDownloadValidatorFactory;
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
                'metadata' => $this->addressMetadata,
                'entityTypeCode' => AddressMetadataInterface::ENTITY_TYPE_ADDRESS,
            ]
        );
        return $validator->canDownloadFile(
            $fileName,
            $customAttributes,
        );
    }
}
