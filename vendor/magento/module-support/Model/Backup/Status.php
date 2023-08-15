<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Backup;

/**
 * Backups status
 */
class Status
{
    /**
     * @var \Magento\Backend\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Magento\Support\Model\DataFormatter
     */
    protected $dataFormatter;

    /**
     * @param \Magento\Backend\Helper\Data $dataHelper
     * @param \Magento\Support\Model\DataFormatter $dataFormatter
     */
    public function __construct(
        \Magento\Backend\Helper\Data $dataHelper,
        \Magento\Support\Model\DataFormatter $dataFormatter
    ) {
        $this->dataHelper = $dataHelper;
        $this->dataFormatter = $dataFormatter;
    }

    /**
     * Get Code or DB Dump Value
     *
     * @param \Magento\Support\Model\Backup\AbstractItem $item
     * @return array
     */
    public function getValue($item)
    {
        $result = [
            'isLink' => 0,
            'link' => __('Unknown Status')
        ];
        $processingStatus = __('Processing ...');
        $statuses = [
            \Magento\Support\Model\Backup\AbstractItem::STATUS_PROCESSING => [
                'isLink' => 0,
                'link' => $processingStatus
            ],
            \Magento\Support\Model\Backup\AbstractItem::STATUS_COMPLETE   => [
                'isLink' => 1,
                'link' => $this->getLink($item)
            ]
        ];

        if (isset($statuses[$item->getStatus()])) {
            $result = $statuses[$item->getStatus()];
        }

        return $result;
    }

    /**
     * Get Link for Code or DB Dump
     *
     * @param \Magento\Support\Model\Backup\AbstractItem $item
     * @return string
     */
    protected function getLink($item)
    {
        $params = ['backup_id' => $item->getBackupId(), 'type' => $item->getType()];
        $link = $this->dataHelper->getUrl('support/backup/download', $params);

        return $link;
    }

    /**
     * Get Code Dump label
     *
     * @param \Magento\Support\Model\Backup\AbstractItem $item
     * @return string
     */
    public function getCodeDumpLabel($item)
    {
        return $item->getName();
    }

    /**
     * Get Db Dump label
     *
     * @param \Magento\Support\Model\Backup\AbstractItem $item
     * @return string
     */
    public function getDbDumpLabel($item)
    {
        return $item->getDbName();
    }

    /**
     * Get Code or DB dump size
     *
     * @param \Magento\Support\Model\Backup\AbstractItem $item
     * @return string
     */
    public function getSize($item)
    {
        return $this->dataFormatter->formatBytes($item->getSize());
    }
}
