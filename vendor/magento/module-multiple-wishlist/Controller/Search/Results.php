<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MultipleWishlist\Controller\Search;

use Magento\Framework\Controller\ResultFactory;

class Results extends \Magento\MultipleWishlist\Controller\Search
{
    /**
     * Wishlist search action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $this->_view->loadLayout();

        try {
            $params = $this->getRequest()->getParam('params');
            if (empty($params) || !is_array($params) || empty($params['search'])) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Please reenter your search options.'));
            }

            $strategy = null;
            switch ($params['search']) {
                case 'type':
                    $strategy = $this->_strategyNameFactory->create();
                    break;
                case 'email':
                    $strategy = $this->_strategyEmailFactory->create();
                    break;
                default:
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Please reenter your search options.')
                    );
            }

            $strategy->setSearchParams($params);
            /** @var \Magento\MultipleWishlist\Model\Search $search */
            $search = $this->_searchFactory->create();
            $this->_coreRegistry->register('search_results', $search->getResults($strategy));
            $this->_customerSession->setLastWishlistSearchParams($params);
        } catch (\InvalidArgumentException $e) {
            $this->messageManager->addNotice($e->getMessage());
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addError(__('We could not perform the search.'));
        }

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->set(__('Wish List Search'));
        return $resultPage;
    }
}
