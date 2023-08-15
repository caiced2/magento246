<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Model\Customer;

use Magento\Customer\Api\MetadataInterface;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\Api\AttributeInterfaceFactory;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Validator validates if user can have access to requested file
 */
class FileDownloadValidator
{
    /**
     * List of file input types
     */
    private const INPUT_TYPES = [
        'file',
        'image'
    ];

    /**
     * @var TemporaryFileStorageInterface
     */
    private $storage;

    /**
     * @var MetadataInterface
     */
    private $metadata;

    /**
     * @var string
     */
    private $entityTypeCode;

    /**
     * @var AttributeInterfaceFactory
     */
    private $attributeFactory;

    /**
     * @param TemporaryFileStorageInterface $storage
     * @param MetadataInterface $metadata
     * @param AttributeInterfaceFactory $attributeFactory
     * @param string $entityTypeCode
     */
    public function __construct(
        TemporaryFileStorageInterface $storage,
        MetadataInterface $metadata,
        AttributeInterfaceFactory $attributeFactory,
        string $entityTypeCode
    ) {
        $this->storage = $storage;
        $this->metadata = $metadata;
        $this->entityTypeCode = $entityTypeCode;
        $this->attributeFactory = $attributeFactory;
    }

    /**
     * Check if customer can download file
     *
     * @param string $fileName
     * @param AttributeInterface[] $customAttributes
     * @return bool
     */
    public function canDownloadFile(
        string $fileName,
        array $customAttributes
    ): bool {
        $fileName = ltrim($fileName, DIRECTORY_SEPARATOR);
        foreach ($customAttributes as $attribute) {
            if ($this->validate($attribute, $fileName)) {
                return true;
            }
        }

        return $this->canDownloadTemporaryFile($fileName);
    }

    /**
     * Check if the file is a temporary file
     *
     * @param string $fileName
     * @return bool
     */
    private function canDownloadTemporaryFile(string $fileName): bool
    {
        $tmpFiles = $this->storage->get();
        if (isset($tmpFiles[$this->entityTypeCode])) {
            foreach ($tmpFiles[$this->entityTypeCode] as $attributeCode => $value) {
                $attribute = $this->attributeFactory->create(
                    [
                        'data' => [
                            AttributeInterface::ATTRIBUTE_CODE => $attributeCode,
                            AttributeInterface::VALUE => $value,
                        ]
                    ]
                );
                if ($this->validate($attribute, $fileName)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Validate attribute value with provided file name
     *
     * @param AttributeInterface $attribute
     * @param string $file
     * @return bool
     */
    private function validate(AttributeInterface $attribute, string $file): bool
    {
        try {
            $valid = false;
            if ($attribute->getValue() && $file === ltrim($attribute->getValue(), DIRECTORY_SEPARATOR)) {
                $attributeMeta = $this->metadata->getAttributeMetadata($attribute->getAttributeCode());
                if (in_array($attributeMeta->getFrontendInput(), self::INPUT_TYPES, true)) {
                    $valid = true;
                }
            }
        } catch (NoSuchEntityException $e) {
            $valid = false;
        }

        return $valid;
    }
}
