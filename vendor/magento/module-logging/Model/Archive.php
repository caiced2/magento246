<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Logging\Model;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Log archive file model
 */
class Archive extends \Magento\Framework\DataObject
{
    /**
     * Full system name to current file, if set
     *
     * @var string
     */
    protected $file = '';

    /**
     * @var \Magento\Framework\Filesystem\Directory\Write
     */
    protected $directory;

    /**
     * @param \Magento\Framework\Filesystem $fileSystem
     */
    public function __construct(\Magento\Framework\Filesystem $fileSystem)
    {
        $this->directory = $fileSystem->getDirectoryWrite(DirectoryList::VAR_DIR);
    }

    /**
     * Storage base path getter
     *
     * @return string
     */
    public function getBasePath()
    {
        return $this->directory->getAbsolutePath('logging/archive');
    }

    /**
     * Check base name syntax
     *
     * @param string $baseName
     * @return bool
     */
    protected function _validateBaseName($baseName)
    {
        return (bool)preg_match('/^[0-9]{10}\.csv$/', $baseName);
    }

    /**
     * Search the file in storage by base name and set it
     *
     * @param string $baseName
     * @return $this
     */
    public function loadByBaseName($baseName)
    {
        $this->file = '';
        $this->unsBaseName();
        if (!$this->_validateBaseName($baseName)) {
            return $this;
        }
        $filename = $this->generateFilename($baseName);
        if (!$this->directory->isFile($this->directory->getRelativePath($filename))) {
            return $this;
        }
        $this->setBaseName($baseName);
        $this->file = $filename;
        return $this;
    }

    /**
     * Generate a full system filename from base name
     *
     * @param string $baseName
     * @return string
     */
    public function generateFilename($baseName)
    {
        return $this->getBasePath() . '/' . substr($baseName, 0, 4) . '/' . substr($baseName, 4, 2) . '/' . $baseName;
    }

    /**
     * Full system filename getter
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->file;
    }

    /**
     * Get file contents, if any
     *
     * @return string
     */
    public function getContents()
    {
        if ($this->file) {
            return $this->directory->readFile($this->directory->getRelativePath($this->file));
        }
        return '';
    }

    /**
     * Mime-type getter
     *
     * @return string
     */
    public function getMimeType()
    {
        return 'text/csv';
    }

    /**
     * Attempt to create a new file using specified base name
     * Or generate a base name from current date/time
     *
     * @param string $baseName
     * @return bool
     */
    public function createNew($baseName = '')
    {
        if (!$baseName) {
            $baseName = date('YmdH') . '.csv';
        }
        if (!$this->_validateBaseName($baseName)) {
            return false;
        }

        $filename = $this->generateFilename($baseName);
        $this->directory->touch($this->directory->getRelativePath($filename));

        $this->loadByBaseName($baseName);
        return true;
    }
}
