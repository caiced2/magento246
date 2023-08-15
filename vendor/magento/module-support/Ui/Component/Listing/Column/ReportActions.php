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
 * Class for Component Listing Column ReportActions
 */
class ReportActions extends Column
{
    /** Url path */
    const REPORT_URL_PATH_VIEW = 'support/report/view';
    const REPORT_URL_PATH_DOWNLOAD = 'support/report/download';
    const REPORT_URL_PATH_DELETE = 'support/report/delete';

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
                if (isset($item['report_id'])) {
                    $item[$name]['view'] = [
                        'href' => $this->urlBuilder->getUrl(self::REPORT_URL_PATH_VIEW, ['id' => $item['report_id']]),
                        'label' => __('View'),
                    ];
                    $item[$name]['delete'] = [
                        'href' => $this->urlBuilder->getUrl(self::REPORT_URL_PATH_DELETE, ['id' => $item['report_id']]),
                        'label' => __('Delete'),
                        'confirm' => [
                            'title' => __('Delete "%1"', ['${ $.$data.report_id }']),
                            'message' => __(
                                'Are you sure you want to delete a "%1" record?',
                                ['${ $.$data.report_id }']
                            ),
                            '__disableTmpl' => ['title' => false, 'message' => false],
                        ],
                    ];
                    $item[$name]['download'] = [
                        'href' => $this->urlBuilder->getUrl(
                            self::REPORT_URL_PATH_DOWNLOAD,
                            ['id' => $item['report_id']]
                        ),
                        'label' => __('Download'),
                    ];
                    $item['report_data'] = null;
                }
            }
        }

        return $dataSource;
    }
}
