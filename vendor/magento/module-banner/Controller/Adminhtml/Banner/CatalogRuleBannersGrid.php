<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Controller\Adminhtml\Banner;

class CatalogRuleBannersGrid extends \Magento\Banner\Controller\Adminhtml\Banner
{
    /**
     * Banner binding tab grid action on catalog rule
     *
     * @return void
     */
    public function execute()
    {
        $ruleId = $this->getRequest()->getParam('id');

        /** @var \Magento\CatalogRule\Api\CatalogRuleRepositoryInterface $ruleRepository */
        $ruleRepository = $this->_objectManager->get(
            \Magento\CatalogRule\Api\CatalogRuleRepositoryInterface::class
        );

        if ($ruleId) {
            try {
                $model = $ruleRepository->get($ruleId);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
                $this->messageManager->addError(__('This rule no longer exists.'));
                $this->_redirect('adminhtml/*');
                return;
            }
        } else {
            /** @var \Magento\CatalogRule\Model\Rule $model */
            $model = $this->_objectManager->create(\Magento\CatalogRule\Model\Rule::class);
        }

        if (!$this->_registry->registry('current_promo_catalog_rule')) {
            $this->_registry->register('current_promo_catalog_rule', $model);
        }
        $this->_view->loadLayout();
        $this->_view->getLayout()
            ->getBlock('related_catalogrule_banners_grid')
            ->setSelectedCatalogruleBanners($this->getRequest()->getPost('selected_catalogrule_banners'));

        $this->_view->renderLayout();
    }
}
