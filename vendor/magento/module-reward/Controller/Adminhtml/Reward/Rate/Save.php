<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Controller\Adminhtml\Reward\Rate;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;

class Save extends \Magento\Reward\Controller\Adminhtml\Reward\Rate implements HttpPostActionInterface
{
    /**
     * Save Action
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPost('rate');

        if ($data) {
            $rate = $this->_initRate();

            if ($this->getRequest()->getParam('rate_id') && !$rate->getId()) {
                return $this->_redirect('adminhtml/*/');
            }

            $rate->addData($data);

            try {
                $rate->save();
                $this->messageManager->addSuccess(__('You saved the rate.'));
            } catch (\Exception $exception) {
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($exception);
                $this->messageManager->addError(__('We can\'t save this rate right now.'));
                return $this->_redirect('adminhtml/*/edit', ['rate_id' => $rate->getId(), '_current' => true]);
            }
        }

        return $this->_redirect('adminhtml/*/');
    }
}
