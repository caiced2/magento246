<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reward\Controller\Adminhtml\Reward\Rate;

class Validate extends \Magento\Reward\Controller\Adminhtml\Reward\Rate
{
    /**
     * Validate Action
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $response = new \Magento\Framework\DataObject(['error' => false]);
        $post = $this->getRequest()->getParam('rate');
        $message = null;
        /** @var \Magento\Store\Model\StoreManagerInterface $storeManager */
        $storeManager = $this->_objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);
        if ($storeManager->isSingleStoreMode()) {
            $post['website_id'] = $storeManager->getStore(true)->getWebsiteId();
        }

        if (!isset(
            $post['customer_group_id']
        ) || !isset(
            $post['website_id']
        ) || !isset(
            $post['direction']
        ) || !isset(
            $post['value']
        ) || !isset(
            $post['equal_value']
        )
        ) {
            $message = __('Please enter all rate information.');
        } elseif ($post['direction'] == \Magento\Reward\Model\Reward\Rate::RATE_EXCHANGE_DIRECTION_TO_CURRENCY &&
            ((int)$post['value'] <= 0 ||
            (double)$post['equal_value'] <= 0)
        ) {
            if ((int)$post['value'] <= 0) {
                $message = __('Please enter a positive integer number in the left rate field.');
            } else {
                $message = __('Please enter a positive number in the right-side rate field.');
            }
        } elseif ($post['direction'] == \Magento\Reward\Model\Reward\Rate::RATE_EXCHANGE_DIRECTION_TO_POINTS &&
            ((double)$post['value'] <= 0 ||
            (int)$post['equal_value'] <= 0)
        ) {
            if ((int)$post['equal_value'] <= 0) {
                $message = __('Please enter a positive integer number in the right-side rate field.');
            } else {
                $message = __('Please enter a positive number in the left-side rate field.');
            }
        } else {
            $rate = $this->_initRate();
            $isRateUnique = $rate->getIsRateUniqueToCurrent(
                $post['website_id'],
                $post['customer_group_id'],
                $post['direction']
            );

            if (!$isRateUnique) {
                $message = __(
                    'Sorry, but a rate for the same website, '
                    . 'customer group and direction or covering rate already exists.'
                );
            }
        }

        if ($message) {
            $this->messageManager->addError($message);
            $this->_view->getLayout()->initMessages();
            $response->setError(true);
            $response->setHtmlMessage($this->_view->getLayout()->getMessagesBlock()->getGroupedHtml());
        }

        $this->getResponse()->representJson($response->toJson());
    }
}
