<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller\Guest;

use Magento\Rma\Model\Rma;

class Returns extends \Magento\Rma\Controller\Guest
{
    /**
     * View all returns
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if (!$this->rmaHelper->isEnabled()) {
            return $this->resultForwardFactory->create()->forward('noroute');
        }

        $result = $this->salesGuestHelper->loadValidOrder($this->_request);
        if ($result instanceof \Magento\Framework\Controller\ResultInterface) {
            return $result;
        }

        $resultPage = $this->resultPageFactory->create();
        $this->_objectManager->get(\Magento\Sales\Helper\Guest::class)->getBreadcrumbs($resultPage);
        return $resultPage;
    }
}
