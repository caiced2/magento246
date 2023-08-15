<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Controller\Adminhtml\Rma;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\DataObject;
use Magento\Rma\Controller\Adminhtml\Rma as AdminhtmlRma;
use Magento\Rma\Model\Rma;
use Magento\Framework\App\Action\HttpPostActionInterface;

/**
 * Show Shipping Methods for RMA
 */
class ShowShippingMethods extends AdminhtmlRma implements HttpPostActionInterface
{
    /**
     * Shows available shipping methods.
     *
     * @return void
     * @throws LocalizedException
     */
    public function execute()
    {
        $responseAjax = new DataObject;
        try {
            /** @var $model Rma */
            $model = $this->_initModel();
            if ($model->getId()) {
                $model->validateOrderItems();
            } else {
                throw new LocalizedException(__('This is the wrong RMA ID.'));
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $responseAjax->setError(true);
            $responseAjax->setMessage($e->getMessage());
        } catch (\Exception $e) {
            $responseAjax->setError(true);
            $responseAjax->setMessage(__('We cannot display the available shipping methods.'));
        }

        if ($responseAjax->getError()) {
            $this->getResponse()->representJson($responseAjax->toJson());
        } else {
            $this->_view->loadLayout();
            $responseAjax = $this->_view->getLayout()->getBlock('magento_rma_shipping_available')->toHtml();
            $this->getResponse()->setBody($responseAjax);
        }
    }
}
