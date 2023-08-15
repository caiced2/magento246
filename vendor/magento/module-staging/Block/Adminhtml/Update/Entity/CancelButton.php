<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Block\Adminhtml\Update\Entity;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class CancelButton
 *
 * @codeCoverageIgnore
 */
class CancelButton implements ButtonProviderInterface
{
    /**
     * @var string
     */
    protected $jsUpdateModal;

    /**
     * @param string $jsUpdateModal
     */
    public function __construct(
        $jsUpdateModal
    ) {
        $this->jsUpdateModal = $jsUpdateModal;
    }

    /**
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Cancel'),
            'class' => 'back',
            'sort_order' => 10,
            'on_click' => '',
            'data_attribute' => [
                'mage-init' => [
                    'Magento_Ui/js/form/button-adapter' => [
                        'actions' => [
                            [
                                'targetName' => $this->jsUpdateModal,
                                'actionName' => 'closeModal'
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
