<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model\Update\Grid\UpdateEditGridActions;

use Magento\Staging\Model\Update\Grid\ActionDataProviderInterface;

/**
 * Class DeleteAction
 */
class DeleteAction implements ActionDataProviderInterface
{
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
     * UpdateActions constructor.
     * @param string $entityIdentifier
     * @param string $entityColumn
     * @param string $jsModalProvider
     * @param string $jsLoaderProvider
     */
    public function __construct(
        $entityIdentifier,
        $entityColumn,
        $jsModalProvider,
        $jsLoaderProvider
    ) {
        $this->entityIdentifier = $entityIdentifier;
        $this->entityColumn = $entityColumn;
        $this->jsModalProvider = $jsModalProvider;
        $this->jsLoaderProvider = $jsLoaderProvider;
    }

    /**
     * Returns actions list for upcoming campaign
     *
     * @param array $item
     * @return array
     */
    public function getActionData($item)
    {
        return [
            'delete' => [
                'callback' => [
                    [
                        'provider' => $this->jsModalProvider,
                        'target' => 'openModal',
                    ],
                    [
                        'provider' => $this->jsLoaderProvider,
                        'target' => 'render',
                        'params' => [
                            $this->entityIdentifier => $item[$this->entityColumn],
                            'update_id' => $item['created_in']
                        ],
                    ]
                ],
                'label' => __('Delete Update'),
                'href' => '#'
            ]
        ];
    }
}
