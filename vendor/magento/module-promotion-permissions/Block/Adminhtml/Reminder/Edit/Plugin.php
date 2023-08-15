<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PromotionPermissions\Block\Adminhtml\Reminder\Edit;

use Magento\PromotionPermissions\Helper\Data as DataHelper;
use Magento\Reminder\Block\Adminhtml\Reminder\Edit as ReminderEditBlock;
use Magento\Backend\Block\Widget\Button\Item as ButtonItemWidget;

class Plugin
{
    /**
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * @param DataHelper $dataHelper
     */
    public function __construct(DataHelper $dataHelper)
    {
        $this->dataHelper = $dataHelper;
    }

    /**
     * Check whether button can be rendered
     *
     * @param ReminderEditBlock $subject
     * @param bool $result
     * @param ButtonItemWidget $item
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCanRender(ReminderEditBlock $subject, $result, ButtonItemWidget $item)
    {
        if ($result && !$this->dataHelper->getCanAdminEditReminderRules()) {
            return !in_array($item->getId(), ['delete', 'save', 'save_and_continue_edit', 'reset', 'run_now']);
        }

        return $result;
    }
}
