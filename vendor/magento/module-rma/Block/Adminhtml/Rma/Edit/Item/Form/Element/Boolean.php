<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Block\Adminhtml\Rma\Edit\Item\Form\Element;

/**
 * RMA Item Widget Form Boolean Element Block.
 */
class Boolean extends \Magento\Framework\Data\Form\Element\Select
{
    /**
     * Render boolean RMA attribute as select with Yes and No values.
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setValues([['label' => __('No'), 'value' => 0], ['label' => __('Yes'), 'value' => 1]]);
    }
}
