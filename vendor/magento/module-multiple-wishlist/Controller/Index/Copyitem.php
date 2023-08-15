<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MultipleWishlist\Controller\Index;

use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\NotFoundException;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\MultipleWishlist\Model\ItemManager;
use Magento\Wishlist\Controller\WishlistProviderInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Wishlist\Model\ItemFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Copyitem extends \Magento\MultipleWishlist\Controller\AbstractIndex
{
    /**
     * @var WishlistProviderInterface
     */
    protected $wishlistProvider;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\MultipleWishlist\Model\ItemManager
     */
    protected $itemManager;

    /**
     * @var ItemFactory
     */
    protected $itemFactory;

    /**
     * @var Validator
     */
    protected $formKeyValidator;

    /**
     * @param Context $context
     * @param WishlistProviderInterface $wishlistProvider
     * @param Session $customerSession
     * @param ItemManager $itemManager
     * @param ItemFactory $itemFactory
     * @param Validator $formKeyValidator
     */
    public function __construct(
        Context $context,
        WishlistProviderInterface $wishlistProvider,
        Session $customerSession,
        ItemManager $itemManager,
        ItemFactory $itemFactory,
        Validator $formKeyValidator
    ) {
        $this->wishlistProvider = $wishlistProvider;
        $this->customerSession = $customerSession;
        $this->itemManager = $itemManager;
        $this->itemFactory = $itemFactory;
        $this->formKeyValidator = $formKeyValidator;
        parent::__construct($context);
    }

    /**
     * Copy wishlist item to given wishlist
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws NotFoundException
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

        $requestParams = $this->getRequest()->getParams();
        if ($this->customerSession->getBeforeWishlistRequest()) {
            $requestParams = $this->customerSession->getBeforeWishlistRequest();
            $this->customerSession->unsBeforeWishlistRequest();
        }

        $wishlist = $this->wishlistProvider->getWishlist(
            isset($requestParams['wishlist_id']) ? $requestParams['wishlist_id'] : null
        );
        if (!$wishlist) {
            throw new NotFoundException(__('Page not found.'));
        }
        $itemId = isset($requestParams['item_id']) ? $requestParams['item_id'] : null;
        $qty = isset($requestParams['qty']) ? $requestParams['qty'] : null;
        if ($itemId) {
            $productName = '';
            try {
                /* @var \Magento\Wishlist\Model\Item $item */
                $item = $this->itemFactory->create();
                $item->loadWithOptions($itemId);

                $wishlistName = $this->_objectManager->get(\Magento\Framework\Escaper::class)
                    ->escapeHtml($wishlist->getName());

                $productName = $this->_objectManager->get(
                    \Magento\Framework\Escaper::class
                )->escapeHtml(
                    $item->getProduct()->getName()
                );

                $this->itemManager->copy($item, $wishlist, $qty);
                $this->messageManager->addSuccess(__('"%1" was copied to %2.', $productName, $wishlistName));
                $this->_objectManager->get(\Magento\Wishlist\Helper\Data::class)->calculate();
            } catch (\InvalidArgumentException $e) {
                $this->messageManager->addError(__('We can\'t find the item.'));
            } catch (\DomainException $e) {
                $this->messageManager->addError(__('"%1" is already present in %2.', $productName, $wishlistName));
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
                if ($productName) {
                    $message = __('We can\'t copy "%1".', $productName);
                } else {
                    $message = __('We can\'t copy the wish list item.');
                }
                $this->messageManager->addError($message);
            }
        }
        $wishlist->save();

        if ($this->customerSession->hasBeforeWishlistUrl()) {
            $resultRedirect->setUrl($this->customerSession->getBeforeWishlistUrl());
            $this->customerSession->unsBeforeWishlistUrl();
        } else {
            $resultRedirect->setUrl($this->_redirect->getRedirectUrl());
        }
        return $resultRedirect;
    }
}
