<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Ui\Component\Listing\Column\Entity;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Staging\Model\Preview\UrlBuilder;
use Magento\Staging\Model\VersionHistoryInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Staging\Model\Update\Grid\ActionDataProviderInterface;

/**
 * Class UpdateActions
 */
class UpdateActions extends Column
{
    /**
     * @var UrlBuilder
     */
    protected $previewUrlBuilder;

    /**
     * @var VersionHistoryInterface
     */
    private $history;

    /**
     * @var ActionDataProviderInterface
     */
    private $actionsDataProvider;

    /**
     * UpdateActions constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param VersionHistoryInterface $history
     * @param ActionDataProviderInterface $actionsList
     * @param array $components
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        VersionHistoryInterface $history,
        ActionDataProviderInterface $actionsList,
        array $components = [],
        array $data = []
    ) {
        $this->history = $history;
        $this->actionsDataProvider = $actionsList;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Returns actions list for upcoming update
     *
     * @param array $item
     * @return array
     */
    private function getUpcomingAction($item)
    {
        return $this->actionsDataProvider->getActionData($item);
    }

    /**
     * Returns dummy action for currently active update
     *
     * @return array
     */
    private function getCurrentPlaceholder()
    {
        return [
            'edit' => [
                'callback' => [],
                'label' => __('This action is unavailable'),
                'href' => '#'
            ],
        ];
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
                if (isset($item['created_in']) && $item['created_in'] > $this->history->getCurrentId()) {
                    $item[$this->getData('name')] = $this->getUpcomingAction($item);
                } else {
                    $item[$this->getData('name')] = $this->getCurrentPlaceholder();
                }
            }
        }
        return $dataSource;
    }
}
