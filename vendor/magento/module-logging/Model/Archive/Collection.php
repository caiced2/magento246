<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Archive files collection
 */
namespace Magento\Logging\Model\Archive;

use Magento\Framework\App\Filesystem\DirectoryList;

class Collection extends \Magento\Framework\Data\Collection\Filesystem
{
    /**
     * Filenames regex filter
     *
     * @var string
     */
    protected $_allowedFilesMask = '/^[a-z0-9\.\-\_]+\.csv$/i';

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $_localeResolver;

    /**
     * Set target dir for scanning
     *
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param \Magento\Logging\Model\Archive $archive
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Magento\Framework\Filesystem $filesystem
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Magento\Logging\Model\Archive $archive,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Framework\Filesystem $filesystem
    ) {
        parent::__construct($entityFactory, $filesystem);
        $basePath = $archive->getBasePath();
        $dir = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $dir->create($dir->getRelativePath($basePath));
        $this->addTargetDir($basePath);
        $this->_localeResolver = $localeResolver;
    }

    /**
     * Row generator
     * Add 'time' column as \DateTime object
     * Add 'timestamp' column as unix timestamp - used in date filter
     *
     * @param string $filename
     * @return array
     */
    protected function _generateRow($filename)
    {
        $row = parent::_generateRow($filename);
        $date = \DateTime::createFromFormat('YmdH', str_replace('.csv', '', $row['basename']));
        $row['time'] = $date;
        /**
         * Used in date filter, because $date contains hours
         */
        $dateWithoutHours = \DateTime::createFromFormat('YmdH', str_replace('.csv', '', $row['basename']));
        $row['timestamp'] = $dateWithoutHours->format('Y-m-d');
        return $row;
    }

    /**
     * Custom callback method for 'moreq' fancy filter
     *
     * @param string $field
     * @param mixed $filterValue
     * @param array $row
     * @return bool
     * @see addFieldToFilter()
     * @see addCallbackFilter()
     */
    public function filterCallbackIsMoreThan($field, $filterValue, $row)
    {
        $rowValue = $row[$field];
        if ($field == 'time') {
            $rowValue = $row['timestamp'];
        }
        return $rowValue > $filterValue;
    }

    /**
     * Custom callback method for 'lteq' fancy filter
     *
     * @param string $field
     * @param mixed $filterValue
     * @param array $row
     * @return bool
     * @see addFieldToFilter()
     * @see addCallbackFilter()
     */
    public function filterCallbackIsLessThan($field, $filterValue, $row)
    {
        $rowValue = $row[$field];
        if ($field == 'time') {
            $rowValue = $row['timestamp'];
        }
        return $rowValue < $filterValue;
    }
}
