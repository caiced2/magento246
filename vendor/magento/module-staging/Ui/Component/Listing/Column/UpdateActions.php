<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Ui\Component\Listing\Column;

use Magento\Staging\Model\Preview\UrlBuilder;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Staging\Model\VersionManager;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class UpdateActions
 */
class UpdateActions extends Column
{
    /**
     * Url path
     */
    const URL_PATH_EDIT = 'staging/update/edit';

    /**
     * Preview url
     */
    const URL_PATH_PREVIEW = 'staging/update/preview';

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var UrlBuilder
     */
    protected $previewUrlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param UrlBuilder $previewUrlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        UrlBuilder $previewUrlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->previewUrlBuilder = $previewUrlBuilder;
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
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['id'])) {
                    $item[$this->getData('name')] = [
                        'edit' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_EDIT,
                                [
                                    'id' => $item['id']
                                ]
                            ),
                            'label' => __('View/Edit')
                        ],
                        'preview' => [
                            'href' => $this->previewUrlBuilder->getPreviewUrl($item['id']),
                            'label' => __('Preview')
                        ]
                    ];
                }
            }
        }
        return $dataSource;
    }
}
