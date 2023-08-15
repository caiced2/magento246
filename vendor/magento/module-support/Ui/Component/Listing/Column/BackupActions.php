<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

/**
 * Class for Component Listing Column BackupActions
 */
class BackupActions extends Column
{
    /** Url path */
    const BACKUP_URL_PATH_SHOW_LOG = 'support/backup/log';
    const BACKUP_URL_PATH_DELETE = 'support/backup/delete';

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
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
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                if (isset($item['backup_id'])) {
                    $item[$name]['log'] = [
                        'href' => $this->urlBuilder->getUrl(
                            self::BACKUP_URL_PATH_SHOW_LOG,
                            ['id' => $item['backup_id']]
                        ),
                        'label' => __('Show Log'),
                    ];
                    $item[$name]['delete'] = [
                        'href' => $this->urlBuilder->getUrl(self::BACKUP_URL_PATH_DELETE, ['id' => $item['backup_id']]),
                        'label' => __('Delete'),
                        'confirm' => [
                            'title' => __('Delete "%1"', ['${ $.$data.backup_id }']),
                            'message' => __(
                                'Are you sure you want to delete a "%1" record?',
                                ['${ $.$data.backup_id }']
                            ),
                            '__disableTmpl' => ['title' => false, 'message' => false],
                        ],
                    ];
                }
            }
        }

        return $dataSource;
    }
}
