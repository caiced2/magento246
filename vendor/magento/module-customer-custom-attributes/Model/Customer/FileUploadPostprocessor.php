<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Model\Customer;

/**
 * Customer temp files persistor
 */
class FileUploadPostprocessor
{
    /**
     * @var TemporaryFileStorageInterface
     */
    private $storage;

    /**
     * @var string
     */
    private $entityTypeCode;

    /**
     * @param TemporaryFileStorageInterface $storage
     * @param string $entityTypeCode
     */
    public function __construct(
        TemporaryFileStorageInterface $storage,
        string $entityTypeCode
    ) {
        $this->storage = $storage;
        $this->entityTypeCode = $entityTypeCode;
    }

    /**
     * Persist temp files in the storage for preview
     *
     * @param string $attributeCode
     * @param string $file
     */
    public function process(string $attributeCode, string $file): void
    {
        $tmpFiles = $this->storage->get();
        $tmpFiles[$this->entityTypeCode][$attributeCode] = $file;
        $this->storage->set($tmpFiles);
    }
}
