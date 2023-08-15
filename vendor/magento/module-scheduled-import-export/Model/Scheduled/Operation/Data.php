<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScheduledImportExport\Model\Scheduled\Operation;

/**
 * Operation Data model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Data
{
    /**
     * Pending status constant
     */
    const STATUS_PENDING = 2;

    /**
     * Storage key for FTP
     */
    const FTP_STORAGE = 'ftp';

    /**
     * Storage key for local filesystem
     */
    const FILE_STORAGE = 'file';

    /**
     * @var \Magento\ImportExport\Model\Import\ConfigInterface
     */
    protected $_importConfig;

    /**
     * @var \Magento\ImportExport\Model\Export\ConfigInterface
     */
    protected $_exportConfig;

    /**
     * @param \Magento\ImportExport\Model\Import\ConfigInterface $importConfig
     * @param \Magento\ImportExport\Model\Export\ConfigInterface $exportConfig
     */
    public function __construct(
        \Magento\ImportExport\Model\Import\ConfigInterface $importConfig,
        \Magento\ImportExport\Model\Export\ConfigInterface $exportConfig
    ) {
        $this->_importConfig = $importConfig;
        $this->_exportConfig = $exportConfig;
    }

    /**
     * Get statuses option array
     *
     * @return array
     */
    public function getStatusesOptionArray()
    {
        return [1 => __('Enabled'), 0 => __('Disabled')];
    }

    /**
     * Get operations option array
     *
     * @return array
     */
    public function getOperationsOptionArray()
    {
        return ['import' => __('Import'), 'export' => __('Export')];
    }

    /**
     * Get frequencies option array
     *
     * @return array
     */
    public function getFrequencyOptionArray()
    {
        return [
            \Magento\Cron\Model\Config\Source\Frequency::CRON_DAILY => __('Daily'),
            \Magento\Cron\Model\Config\Source\Frequency::CRON_WEEKLY => __('Weekly'),
            \Magento\Cron\Model\Config\Source\Frequency::CRON_MONTHLY => __('Monthly')
        ];
    }

    /**
     * Get server types option array
     *
     * @return array
     */
    public function getServerTypesOptionArray()
    {
        return [self::FILE_STORAGE => __('Local Server'), self::FTP_STORAGE => __('Remote FTP')];
    }

    /**
     * Get file modes option array
     *
     * @return array
     */
    public function getFileModesOptionArray()
    {
        return [FTP_BINARY => __('Binary'), FTP_ASCII => __('ASCII')];
    }

    /**
     * Get forced import option array
     *
     * @return array
     */
    public function getForcedImportOptionArray()
    {
        return [0 => __('Stop Import'), 1 => __('Continue Processing')];
    }

    /**
     * Get operation result option array
     *
     * @return array
     */
    public function getResultOptionArray()
    {
        return [0 => __('Failed'), 1 => __('Successful'), self::STATUS_PENDING => __('Pending')];
    }

    /**
     * Get entities option array
     *
     * @param string $type
     * @return array
     */
    public function getEntitiesOptionArray($type = null)
    {
        $importOptions = [];
        foreach ($this->_importConfig->getEntities() as $entityName => $entityConfig) {
            $importOptions[$entityName] = __($entityConfig['label']);
        }
        $exportOptions = [];
        foreach ($this->_exportConfig->getEntities() as $entityName => $entityConfig) {
            $exportOptions[$entityName] = __($entityConfig['label']);
        }
        switch ($type) {
            case 'import':
                return $importOptions;

            case 'export':
                return $exportOptions;

            default:
                return array_merge($importOptions, $exportOptions);
        }
    }
}
