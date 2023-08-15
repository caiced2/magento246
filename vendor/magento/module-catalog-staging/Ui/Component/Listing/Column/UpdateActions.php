<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Staging\Model\VersionManager;

/**
 * Class UpdateActions
 */
class UpdateActions extends Column
{
    /**
     * Url path
     */
    const URL_PATH_EDIT = 'catalog/product/edit';

    /**
     * Url path
     */
    const URL_PATH_DELETE = 'catalog/product/delete';

    /**
     * View product url
     */
    const URL_PATH_VIEW = 'catalog/product/view';

    /**
     * Preview url
     */
    const URL_PATH_PREVIEW = 'staging/update/preview';

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Framework\Url
     */
    protected $frontendUrl;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param \Magento\Framework\Url $frontendUrl
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        \Magento\Framework\Url $frontendUrl,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->frontendUrl = $frontendUrl;
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
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['entity_id']) && isset($item['id'])) {
                    $item[$this->getData('name')] = [
                        'edit' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_EDIT,
                                [
                                    'id' => $item['entity_id'],
                                    VersionManager::PARAM_NAME => $item['id']
                                ]
                            ),
                            'label' => __('View/Edit')
                        ],
                        'delete' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_DELETE,
                                [
                                    'id' => $item['entity_id'],
                                    VersionManager::PARAM_NAME => $item['id']
                                ]
                            ),
                            'label' => __('Delete')
                        ],
                        'preview' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_PREVIEW,
                                [
                                    '_query' => [
                                        'preview_url' => urlencode($this->frontendUrl->getUrl(
                                            static::URL_PATH_VIEW,
                                            [
                                                'id' => $item['entity_id'],
                                                '_query' => [
                                                    VersionManager::PARAM_NAME => $item['id']
                                                ]
                                            ]
                                        )),
                                        VersionManager::PARAM_NAME => $item['id']
                                    ],
                                ]
                            ),
                            'label' => __('Preview')
                        ]
                    ];
                }
            }
        }
        return $dataSource;
    }
}
