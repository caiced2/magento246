<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Ui\Component\Listing\Column\Entity;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Staging\Model\Preview\UrlBuilder;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Entity for listing column Actions
 */
class Actions extends Column
{
    /**
     * @var UrlBuilder
     */
    protected $previewUrlBuilder;

    /**
     * @var string
     */
    protected $entityIdentifier;

    /**
     * @var string
     */
    protected $entityColumn;

    /**
     * @var string
     */
    protected $jsModalProvider;

    /**
     * @var string
     */
    protected $jsLoaderProvider;

    /**
     * @var UrlProviderInterface
     */
    protected $urlProviderInterface;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlBuilder $previewUrlBuilder
     * @param string $entityIdentifier
     * @param string $entityColumn
     * @param string $jsModalProvider
     * @param string $jsLoaderProvider
     * @param UrlProviderInterface $urlProviderInterface
     * @param array $components
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlBuilder $previewUrlBuilder,
        $entityIdentifier,
        $entityColumn,
        $jsModalProvider,
        $jsLoaderProvider,
        UrlProviderInterface $urlProviderInterface = null,
        array $components = [],
        array $data = []
    ) {
        $this->previewUrlBuilder = $previewUrlBuilder;
        $this->entityIdentifier = $entityIdentifier;
        $this->entityColumn = $entityColumn;
        $this->jsModalProvider = $jsModalProvider;
        $this->jsLoaderProvider = $jsLoaderProvider;
        $this->urlProviderInterface = $urlProviderInterface;
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
                    $url = $this->urlProviderInterface instanceof UrlProviderInterface
                        ? $this->urlProviderInterface->getUrl($item)
                        : null;
                    $store = array_key_exists('store_id', $item) ? $item['store_id'] : null;
                    $item[$this->getData('name')] = [
                        'edit' => [
                            'callback' => [
                                [
                                    'provider' => $this->jsLoaderProvider,
                                    'target' => 'destroyInserted',
                                ],
                                [
                                    'provider' => $this->jsLoaderProvider,
                                    'target' => 'updateData',
                                    'params' => [
                                        $this->entityIdentifier => $item[$this->entityColumn],
                                        'update_id' => $item['id'],
                                    ],
                                ],
                                [
                                    'provider' => $this->jsModalProvider,
                                    'target' => 'openModal',
                                ],
                            ],
                            'label' => __('View/Edit'),
                        ],
                        'preview' => [
                            'href' => $this->previewUrlBuilder->getPreviewUrl($item['id'], $url, $store),
                            'label' => __('Preview'),
                            'target' => '_blank',
                        ],
                    ];
                }
            }
        }

        return $dataSource;
    }
}
