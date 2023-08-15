<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Support\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DriverPool;

/**
 * Helper for work with shell
 */
class Shell extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * File that contains paths to shell commands
     */
    public const PATHS_FILE        = 'Paths.php';

    /**#@+
     * Shell commands
     */
    public const UTILITY_NICE      = 'nice';
    public const UTILITY_TAR       = 'tar';
    public const UTILITY_MYSQLDUMP = 'mysqldump';
    public const UTILITY_GZIP      = 'gzip';
    public const UTILITY_LSOF      = 'lsof';
    public const UTILITY_PHP       = 'php';
    public const UTILITY_SED       = 'sed';
    public const UTILITY_BASH      = 'bash';
    public const UTILITY_MYSQL     = 'mysql';
    /**#@-*/

    public const XML_OUTPUT_PATH   = 'support/output_path';

    /**
     * @var \Magento\Framework\ShellInterface
     */
    protected $shell;

    /**
     * @var \Magento\Framework\Filesystem\Directory\Write
     */
    protected $directory;

    /**
     * @var array
     */
    protected $utilities = [];

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var string
     */
    protected $outputPath;

    /**
     * @var string
     */
    protected $absoluteOutputPath;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var DriverPool
     */
    private $driverPool;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\ShellInterface $shell
     * @param \Magento\Framework\Filesystem $filesystem
     * @param DirectoryList|null $directoryList
     * @param DriverPool|null $driverPool
     * @throws FileSystemException
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ShellInterface $shell,
        Filesystem $filesystem,
        DirectoryList $directoryList = null,
        DriverPool $driverPool = null
    ) {
        parent::__construct($context);
        $this->shell = $shell;
        $this->filesystem = $filesystem;
        $this->directory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $this->directoryList = $directoryList ?: ObjectManager::getInstance()->get(DirectoryList::class);
        $this->driverPool = $driverPool ?: ObjectManager::getInstance()->get(DriverPool::class);
    }

    /**
     * Wrapper for execute
     *
     * @param string $command
     * @param array $arguments
     * @return string
     */
    public function execute($command, array $arguments = [])
    {
        return $this->shell->execute($command, $arguments);
    }

    /**
     * Get paths file path
     *
     * @return string
     */
    public function getPathsFileName()
    {
        return $this->getAbsoluteOutputPath() . '/' . self::PATHS_FILE;
    }

    /**
     * Collect paths for required console utilities
     *
     * @param bool $force
     * @return void
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function initPaths($force = false)
    {
        if (!empty($this->utilities)) {
            return;
        }

        $pathsFile = $this->getPathsFileName();
        if (!$force && $this->directory->isExist($pathsFile)) {
            // phpcs:ignore Magento2.Security.IncludeFile
            $this->utilities = include $pathsFile;
            return;
        }

        $list = [
            self::UTILITY_GZIP,
            self::UTILITY_LSOF,
            self::UTILITY_MYSQLDUMP,
            self::UTILITY_NICE,
            self::UTILITY_PHP,
            self::UTILITY_TAR,
            self::UTILITY_SED,
            self::UTILITY_BASH,
            self::UTILITY_MYSQL
        ];
        foreach ($list as $name) {
            try {
                $this->utilities[$name] = $this->execute('which ' . $name);
            } catch (LocalizedException $e) {
                throw new \Magento\Framework\Exception\NotFoundException(
                    __('The "%1" utility wasn\'t found. Verify the utility and try again.', $name)
                );
            }
        }
    }

    /**
     * Get utility path by utility name
     *
     * @param string $name
     * @return mixed
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function getUtility($name)
    {
        $this->initPaths();

        if (!isset($this->utilities[$name])) {
            throw new \Magento\Framework\Exception\NotFoundException(
                __('The "%1" utility is unknown. Verify the utility and try again.', $name)
            );
        }

        return $this->utilities[$name];
    }

    /**
     * Get utilities.
     *
     * @return array
     */
    public function getUtilities()
    {
        return $this->utilities;
    }

    /**
     * Get output path
     *
     * @return string
     */
    public function getOutputPath()
    {
        if (null === $this->outputPath) {
            $this->outputPath = $this->scopeConfig->getValue(self::XML_OUTPUT_PATH);

            if (!$this->directory->isExist($this->outputPath)) {
                $this->directory->create($this->outputPath);
            }
        }

        return $this->outputPath;
    }

    /**
     * Get absolute output path
     *
     * @return string
     */
    public function getAbsoluteOutputPath()
    {
        if (null === $this->absoluteOutputPath) {
            $relativePath = $this->getOutputPath();

            $this->absoluteOutputPath = $this->directory->getAbsolutePath($relativePath);
        }

        return $this->absoluteOutputPath;
    }

    /**
     * Get Item Path
     *
     * @param string $itemName
     * @return string
     */
    public function getFilePath($itemName)
    {
        return $this->getOutputPath() . $itemName;
    }

    /**
     * Get absolute item path
     *
     * @param string $itemName
     * @return string
     */
    public function getAbsoluteFilePath($itemName)
    {
        return $this->getAbsoluteOutputPath() . $itemName;
    }

    /**
     * Get file size
     *
     * @param string $itemName
     * @return int
     */
    public function getFileSize($itemName)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return filesize($this->getAbsoluteFilePath($itemName));
    }

    /**
     * Check file is locked
     *
     * @param string $filePath
     * @return bool
     * @throws FileSystemException
     */
    public function isFileLocked($filePath)
    {
        $fileAbsolutePath = $this->getAbsoluteFilePath($filePath);

        if (!$this->directory->isFile($fileAbsolutePath)) {
            throw new FileSystemException(__('File %1 is not found.', $filePath));
        }

        // phpcs:ignore Magento2.Security.InsecureFunction,Magento2.Functions.DiscouragedFunction
        return (bool)exec($this->getUtility(self::UTILITY_LSOF) . ' ' . escapeshellarg($fileAbsolutePath));
    }

    /**
     * Check if php can run bash script
     *
     * @return bool
     */
    public function isExecEnabled()
    {
        $disabledFunctions = explode(',', ini_get('disable_functions'));

        return function_exists('exec') && !in_array('exec', $disabledFunctions);
    }

    /**
     * Set working directory on the Magento root directory
     *
     * @return void
     * @throws LocalizedException
     */
    public function setRootWorkingDirectory()
    {
        $magentoRootPath = $this->filesystem->getDirectoryWrite(DirectoryList::ROOT)->getAbsolutePath();
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        if (!chdir($magentoRootPath)) {
            throw new LocalizedException(__("The root category can't be set. Verify the category and try again."));
        }
    }

    /**
     * Validates if at least one path from patter exists
     *
     * @param string $pattern
     * @return bool
     */
    public function pathExists(string $pattern): bool
    {
        try {
            $rootDirectory = $this->directoryList->getRoot();
            return !empty($this->driverPool->getDriver(DriverPool::FILE)->search($pattern, $rootDirectory));
        } catch (FileSystemException $e) {
            return false;
        }
    }
}
