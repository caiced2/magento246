<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScheduledImportExport\Model;

/**
 * Export model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 *
 * @method string getOperationType() getOperationType()
 * @method int getRunDate() getRunDate()
 * @method \Magento\ScheduledImportExport\Model\Export setRunDate() setRunDate(int $value)
 * @method \Magento\ScheduledImportExport\Model\Export setEntity() setEntity(string $value)
 * @method \Magento\ScheduledImportExport\Model\Export setOperationType() setOperationType(string $value)
 */
class Export extends \Magento\ImportExport\Model\Export implements
    \Magento\ScheduledImportExport\Model\Scheduled\Operation\OperationInterface
{
    /**
     * Date model instance
     *
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateModel;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    private $localeResolver;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\ImportExport\Model\Export\ConfigInterface $exportConfig
     * @param \Magento\ImportExport\Model\Export\Entity\Factory $entityFactory
     * @param \Magento\ImportExport\Model\Export\Adapter\Factory $exportAdapterFac
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $coreDate
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param array $data
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\ImportExport\Model\Export\ConfigInterface $exportConfig,
        \Magento\ImportExport\Model\Export\Entity\Factory $entityFactory,
        \Magento\ImportExport\Model\Export\Adapter\Factory $exportAdapterFac,
        \Magento\Framework\Stdlib\DateTime\DateTime $coreDate,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        array $data = []
    ) {
        $this->_dateModel = $coreDate;
        $this->localeResolver = $localeResolver;

        parent::__construct(
            $logger,
            $filesystem,
            $exportConfig,
            $entityFactory,
            $exportAdapterFac,
            $data
        );
    }

    /**
     * Run export through cron
     *
     * @param Scheduled\Operation $operation
     * @return bool
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function runSchedule(Scheduled\Operation $operation)
    {
        $currentLocale = $this->localeResolver->getLocale();
        $locale = $operation->getFileInfo()['locale'] ?? $currentLocale;
        $this->localeResolver->setLocale($locale);

        try {
            $data = $this->export();
        } catch (\Exception $e) {
            $operation->saveFileSource($this, $e->getMessage());
            throw $e;
        } finally {
            $this->localeResolver->setLocale($currentLocale);
        }

        $result = $operation->saveFileSource($this, $data);

        return (bool)$result;
    }

    /**
     * Initialize export instance from scheduled operation
     *
     * @param Scheduled\Operation $operation
     * @return $this
     */
    public function initialize(Scheduled\Operation $operation)
    {
        $fileInfo = $operation->getFileInfo();
        $attributes = $operation->getEntityAttributes();
        $data = [
            'entity' => $operation->getEntityType(),
            'file_format' => $fileInfo['file_format'],
            'export_filter' => $attributes['export_filter'],
            'operation_type' => $operation->getOperationType(),
            'run_at' => $operation->getStartTime(),
            'scheduled_operation_id' => $operation->getId(),
        ];
        if (isset($attributes['skip_attr'])) {
            $data['skip_attr'] = $attributes['skip_attr'];
        }
        $this->setData($data);
        return $this;
    }

    /**
     * Get file name for scheduled running
     *
     * @return string file name without extension
     */
    public function getScheduledFileName()
    {
        $runDate = $this->getRunDate() ? $this->getRunDate() : null;
        return $this->_dateModel->date(
            'Y-m-d_H-i-s',
            $runDate
        ) . '_' . $this->getOperationType() . '_' . $this->getEntity();
    }
}
