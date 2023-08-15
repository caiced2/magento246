<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerSegment\Controller\Adminhtml\Index;

use Exception;
use Magento\CustomerSegment\Model\Segment;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\CustomerSegment\Controller\Adminhtml\Index;
use Magento\Framework\Exception\LocalizedException;

/**
 * Match Segment Customers controller action
 */
class MatchAction extends Index implements HttpGetActionInterface
{
    /**
     * Match segment customers action
     *
     * @return void
     */
    public function execute()
    {
        try {
            $model = $this->_initSegment();
            if ($model->getApplyTo() != Segment::APPLY_TO_VISITORS) {
                $model->matchCustomers();
            }
            $this->messageManager->addSuccessMessage(__(
                'Segment Customers matching is added to messages queue.'
            ));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->_redirect('customersegment/*/');
            return;
        } catch (Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('Segment Customers matching error.')
            );
            $this->_redirect('customersegment/*/');
            return;
        }
        $this->_redirect(
            'customersegment/*/edit',
            ['id' => $model->getId(), 'active_tab' => 'customers_tab']
        );
    }
}
