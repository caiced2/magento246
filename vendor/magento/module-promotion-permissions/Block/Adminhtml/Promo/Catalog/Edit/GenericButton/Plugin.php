<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PromotionPermissions\Block\Adminhtml\Promo\Catalog\Edit\GenericButton;

/**
 * @codeCoverageIgnore
 */
class Plugin
{
    /**
     * @var string[]
     */
    protected $restrictedButtons = [
        'delete', 'save', 'save_and_continue_edit', 'save_apply', 'reset',
    ];

    /**
     * @var bool
     */
    private $canEdit;

    /**
     * @param \Magento\PromotionPermissions\Helper\Data $promoPermData
     */
    public function __construct(\Magento\PromotionPermissions\Helper\Data $promoPermData)
    {
        $this->canEdit = $promoPermData->getCanAdminEditCatalogRules();
    }

    /**
     * Check where button can be rendered
     *
     * @param \Magento\CatalogRule\Block\Adminhtml\Edit\GenericButton $subject
     * @param string $name
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCanRender(
        \Magento\CatalogRule\Block\Adminhtml\Edit\GenericButton $subject,
        $name
    ) {
        $result  = true;
        if (!$this->canEdit) {
            $result = !in_array($name, $this->restrictedButtons);
        }
        return $result;
    }
}
