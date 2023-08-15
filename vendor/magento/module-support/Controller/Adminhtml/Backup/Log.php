<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Controller\Adminhtml\Backup;

/**
 * Show log backup action
 */
class Log extends \Magento\Support\Controller\Adminhtml\Backup
{
    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $pageResult */
        $pageResult = $this->getResultPage();
        $pageResult->getConfig()->getTitle()->prepend(__('Log Details'));
        $pageResult->addBreadcrumb(__('Log Details'), __('Log Details'));

        return $pageResult;
    }
}
