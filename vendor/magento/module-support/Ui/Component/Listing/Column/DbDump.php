<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Support\Model\Backup\Status;

class DbDump extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @var \Magento\Support\Model\BackupFactory
     */
    protected $backupFactory;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Magento\Support\Model\Backup\Status $status
     * @param \Magento\Support\Model\BackupFactory $backupFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Support\Model\Backup\Status $status,
        \Magento\Support\Model\BackupFactory $backupFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->status = $status;
        $this->backupFactory = $backupFactory;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
                if (is_array($item['name'])) {
                    preg_match('/^(.*?)\..*/', $item['name']['label'], $matches);
                    $item['db_name'] = $matches[1];
                } else {
                    $item['db_name'] = $item['name'];
                }
                if (isset($item[$fieldName])) {
                    /** @var \Magento\Support\Model\Backup $backup */
                    $backup = $this->backupFactory->create();
                    foreach ($item as $field => $value) {
                        $backup->setData(strtolower($field), $value);
                    }
                    $items = $backup->getItems();
                    $item[$fieldName] = [
                        'label' => $this->status->getDbDumpLabel($items['db']),
                        'value' => $this->status->getValue($items['db']),
                        'size' => $this->status->getSize($items['db'])
                    ];
                }
            }
        }

        return $dataSource;
    }
}
