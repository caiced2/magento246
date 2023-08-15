<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Controller\Adminhtml\Report\Customer\Customersegment;

use Magento\CustomerSegment\Controller\Adminhtml\Report\Customer\Customersegment;
use Magento\CustomerSegment\Helper\Data;

/**
 * Renders a Customer Segment Report
 *
 * @SuppressWarnings(PHPMD.AllPurposeAction)
 */
class Detail extends Customersegment
{
    /**
     * Detail Action of customer segment
     *
     * @return void
     */
    public function execute()
    {
        if ($this->_initSegment()) {
            // Add help Notice to Combined Report
            if ($this->_getAdminSession()->getMassactionIds()) {
                $collection = $this->_collectionFactory->create()->addFieldToFilter(
                    'segment_id',
                    ['in' => $this->_getAdminSession()->getMassactionIds()]
                );

                $segments = [];
                foreach ($collection as $item) {
                    $segments[] = $item->getName();
                }

                if ($segments) {
                    $viewModeLabel = $this->_objectManager->get(
                        Data::class
                    )->getViewModeLabel(
                        $this->_getAdminSession()->getViewMode()
                    );
                    $this->messageManager->addComplexNoticeMessage(
                        'viewingCombinedReports',
                        [
                            'viewMode' => (string)$viewModeLabel,
                            'segments' => $segments
                        ]
                    );
                }
            }

            $this->_initAction();
            $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Customer Segment Report'));
            $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Details'));
            // phpcs:ignore
            $this->_view->renderLayout();
        } else {
            // phpcs:ignore
            $this->_redirect('*/*/segment');
        }
    }
}
