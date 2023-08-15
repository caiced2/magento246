<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MultipleWishlist\Controller\Index;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Controller\ResultFactory;
use Magento\MultipleWishlist\Model\WishlistEditor;

class Editwishlist extends \Magento\MultipleWishlist\Controller\AbstractIndex
{
    /**
     * @var WishlistEditor
     */
    protected $wishlistEditor;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var Validator
     */
    protected $formKeyValidator;

    /**
     * @param Context $context
     * @param WishlistEditor $wishlistEditor
     * @param Session $customerSession
     * @param Validator $formKeyValidator
     */
    public function __construct(
        Context $context,
        WishlistEditor $wishlistEditor,
        Session $customerSession,
        Validator $formKeyValidator
    ) {
        $this->wishlistEditor = $wishlistEditor;
        $this->customerSession = $customerSession;
        $this->formKeyValidator = $formKeyValidator;
        parent::__construct($context);
    }

    /**
     * Edit wishlist properties
     *
     * @return \Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $resultRedirect->setPath('*/');
        }

        $customerId = $this->customerSession->getCustomerId();
        $wishlistName = $this->getRequest()->getParam('name');
        $visibility = $this->getRequest()->getParam('visibility', 0) === 'on' ? 1 : 0;
        $wishlistId = $this->getRequest()->getParam('wishlist_id');
        $wishlist = null;
        try {
            $wishlist = $this->wishlistEditor->edit($customerId, $wishlistName, $visibility, $wishlistId);

            $this->messageManager->addSuccess(
                __(
                    'Wish list "%1" was saved.',
                    $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($wishlist->getName())
                )
            );
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('We can\'t create the wish list right now.'));
        }

        if (!$wishlist || !$wishlist->getId()) {
            $this->messageManager->addError(__('Could not create a wish list.'));
        }

        if ($this->getRequest()->isAjax()) {
            if ($wishlist && $wishlist->getId()) {
                $params = [
                    'wishlist_id' => $wishlist->getId(),
                    'redirect' => $this->_url->getUrl('wishlist/index/index', ['wishlist_id' => $wishlist->getId()])
                ];
            } else {
                $params = ['redirect' => $this->_url->getUrl('*/*')];
            }
            /** @var \Magento\Framework\Controller\Result\Json $resultJson */
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $resultJson->setData($params);
            return $resultJson;
        }

        if (!$wishlist || !$wishlist->getId()) {
            $resultRedirect->setPath('*/*');
        } else {
            $resultRedirect->setPath('wishlist/index/index', ['wishlist_id' => $wishlist->getId()]);
        }
        return $resultRedirect;
    }
}
