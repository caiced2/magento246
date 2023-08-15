<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Controller\Adminhtml\Banner;

class SalesRuleBannersGrid extends \Magento\Banner\Controller\Adminhtml\Banner
{
    /**
     * Banner binding tab grid action on sales rule
     *
     * @return void
     */
    public function execute()
    {
        $ruleId = $this->getRequest()->getParam('id');
        $model = $this->_objectManager->create(\Magento\SalesRule\Model\Rule::class);

        if ($ruleId) {
            $model->load($ruleId);
            if (!$model->getRuleId()) {
                $this->messageManager->addError(__('This rule no longer exists.'));
                $this->_redirect('adminhtml/*');
                return;
            }
        }
        if (!$this->_registry->registry('current_promo_quote_rule')) {
            $this->_registry->register('current_promo_quote_rule', $model);
        }
        $this->_view->loadLayout();
        $this->_view->getLayout()->getBlock(
            'related_salesrule_banners_grid'
        )->setSelectedSalesruleBanners(
            $this->getRequest()->getPost('selected_salesrule_banners')
        );
        $this->_view->renderLayout();
    }
}
