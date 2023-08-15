<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PromotionPermissions\Block\Adminhtml\Reminder;

use Magento\PromotionPermissions\Helper\Data as DataHelper;
use Magento\Reminder\Block\Adminhtml\Reminder as ReminderBlock;
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
     * @param ReminderBlock $subject
     * @param bool $result
     * @param \Magento\Backend\Block\Widget\Button\Item $item
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCanRender(ReminderBlock $subject, $result, ButtonItemWidget $item)
    {
        if ($result && !$this->dataHelper->getCanAdminEditReminderRules()) {
            return !in_array($item->getId(), ['add']);
        }

        return $result;
    }
}
