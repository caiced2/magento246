<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Block\Adminhtml\Report\Create;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class CreateButton
 */
class CreateButton implements ButtonProviderInterface
{
    /**
     * Create button
     *
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Create'),
            'class' => 'primary'
        ];
    }
}
