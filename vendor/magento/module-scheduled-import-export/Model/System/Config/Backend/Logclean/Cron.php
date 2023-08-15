<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScheduledImportExport\Model\System\Config\Backend\Logclean;

/**
 * Backend model for import/export log cleaning schedule options
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Cron extends \Magento\Framework\App\Config\Value
{
    /**
     * Cron expression configuration path
     */
    const CRON_STRING_PATH = 'crontab/default/jobs/magento_scheduled_import_export_log_clean/schedule/cron_expr';

    /**
     * @var \Magento\Framework\App\Config\ValueFactory
     */
    protected $_configValueFactory;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Config\ValueFactory $configValueFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Config\ValueFactory $configValueFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_configValueFactory = $configValueFactory;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Add cron task
     *
     * @throws \Exception
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function afterSave()
    {
        $time = $this->getData('groups/magento_scheduled_import_export_log/fields/time/value');
        $frequency = $this->getData('groups/magento_scheduled_import_export_log/fields/frequency/value');

        $frequencyDaily = \Magento\Cron\Model\Config\Source\Frequency::CRON_DAILY;
        $frequencyWeekly = \Magento\Cron\Model\Config\Source\Frequency::CRON_WEEKLY;
        $frequencyMonthly = \Magento\Cron\Model\Config\Source\Frequency::CRON_MONTHLY;

        $cronExprArray = [
            intval($time[1]),                                   // Minute
            intval($time[0]),                                   // Hour
            $frequency == $frequencyMonthly ? '1' : '*',        // Day of the Month
            '*',                                                // Month of the Year
            $frequency == $frequencyWeekly ? '1' : '*',          // Day of the Week
        ];

        $cronExprString = join(' ', $cronExprArray);

        try {
            $this->_configValueFactory->create()->load(
                self::CRON_STRING_PATH,
                'path'
            )->setValue(
                $cronExprString
            )->setPath(
                self::CRON_STRING_PATH
            )->save();
        } catch (\Exception $e) {
            throw new \Exception(__('We were unable to save the cron expression.'));
        }
        return parent::afterSave();
    }
}
