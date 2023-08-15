<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PromotionPermissions\Block\Adminhtml\Promo\Quote\Edit\GenericButton;

/**
 * @codeCoverageIgnore
 */
class Plugin
{
    /**
     * @var string[]
     */
    protected $restrictedButtons = ['delete', 'save', 'save_and_continue_edit', 'reset'];

    /**
     * @var bool
     */
    private $canEdit;

    /**
     * @param \Magento\PromotionPermissions\Helper\Data $promoPermData
     */
    public function __construct(\Magento\PromotionPermissions\Helper\Data $promoPermData)
    {
        $this->canEdit = $promoPermData->getCanAdminEditSalesRules();
    }

    /**
     * Check where button can be rendered
     *
     * @param \Magento\SalesRule\Block\Adminhtml\Promo\Quote\Edit\GenericButton $subject
     * @param string $name
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCanRender(
        \Magento\SalesRule\Block\Adminhtml\Promo\Quote\Edit\GenericButton $subject,
        $name
    ) {
        if (!$this->canEdit) {
            return !in_array($name, $this->restrictedButtons);
        }
        return true;
    }
}
