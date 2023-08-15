<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Controller\Adminhtml\Reward\Rate;

use Magento\Framework\App\Action\HttpPostActionInterface;

class Delete extends \Magento\Reward\Controller\Adminhtml\Reward\Rate implements HttpPostActionInterface
{
    /**
     * Delete Action
     *
     * @return void
     */
    public function execute()
    {
        $rate = $this->_initRate();
        if ($rate->getId()) {
            try {
                $rate->delete();
                $this->messageManager->addSuccess(__('You deleted the rate.'));
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_redirect('adminhtml/*/edit', ['_current' => true]);
                return;
            }
        }

        return $this->_redirect('adminhtml/*/');
    }
}
