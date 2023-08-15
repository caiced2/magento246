<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Model\Customer;

use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File as FilesystemClient;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\MediaStorage\Helper\File\Storage;
use Magento\Framework\Filesystem\Directory\ReadInterface;

/**
 * Class FileDownload returns information for file download
 */
class FileDownload
{
    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var ReadInterface
     */
    private $directory;

    /**
     * @var FilesystemClient
     */
    private $filesystemClient;

    /**
     * @var Storage
     */
    private $fileStorage;

    /**
     * @var string
     */
    private $entityType;

    /**
     * @param Filesystem $fileSystem
     * @param FilesystemClient $filesystemClient
     * @param Storage $fileStorage
     * @param string $entityType
     */
    public function __construct(
        FileSystem $fileSystem,
        FilesystemClient $filesystemClient,
        Storage $fileStorage,
        string $entityType = CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER
    ) {
        $this->fileSystem = $fileSystem;
        $this->directory = $this->fileSystem->getDirectoryRead(DirectoryList::MEDIA);
        $this->filesystemClient = $filesystemClient;
        $this->fileStorage = $fileStorage;
        $this->entityType = $entityType;
    }

    /**
     * Returns file name and file path
     *
     * @param string $file
     * @return array
     * @throws NotFoundException
     */
    public function getFilePath(string $file): array
    {
        $fileName = $this->entityType . DIRECTORY_SEPARATOR . ltrim($file, DIRECTORY_SEPARATOR);

        $path = $this->directory->getAbsolutePath($fileName);
        if (mb_strpos($fileName, '..') !== false
            || (!$this->directory->isFile($fileName) && !$this->fileStorage->processStorageFile($path))
        ) {
            throw new NotFoundException(__('Page not found.'));
        }

        return [$fileName, $path];
    }

    /**
     * Returns file path info
     *
     * @param string $path
     * @return array
     */
    public function getPathInfo(string $path): array
    {
        return $this->filesystemClient->getPathInfo($path);
    }

    /**
     * Returns file directory
     *
     * @return ReadInterface
     */
    public function getDirectory(): ReadInterface
    {
        return $this->directory;
    }
}
