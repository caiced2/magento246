<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Controller\Adminhtml\Banner\Widget;

class Chooser extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Banner::magento_banner';

    /**
     * Chooser Source action
     *
     * @return void
     */
    public function execute()
    {
        $uniqId = $this->getRequest()->getParam('uniq_id');

        $bannersGrid = $this->_view->getLayout()->createBlock(
            \Magento\Banner\Block\Adminhtml\Widget\Chooser::class,
            '',
            ['data' => ['id' => $uniqId]]
        );
        $html = $bannersGrid->toHtml();

        $this->getResponse()->setBody($html);
    }
}
