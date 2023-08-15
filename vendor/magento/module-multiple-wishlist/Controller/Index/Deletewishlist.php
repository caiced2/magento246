<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MultipleWishlist\Controller\Index;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\App\Action\Context;
use Magento\Wishlist\Controller\WishlistProviderInterface;

class Deletewishlist extends \Magento\MultipleWishlist\Controller\AbstractIndex
{
    /**
     * @var \Magento\Wishlist\Controller\WishlistProviderInterface
     */
    protected $wishlistProvider;

    /**
     * @var Validator
     */
    protected $formKeyValidator;

    /**
     * @param Context $context
     * @param WishlistProviderInterface $wishlistProvider
     * @param Validator $formKeyValidator
     */
    public function __construct(
        Context $context,
        WishlistProviderInterface $wishlistProvider,
        Validator $formKeyValidator
    ) {
        $this->wishlistProvider = $wishlistProvider;
        $this->formKeyValidator = $formKeyValidator;
        parent::__construct($context);
    }

    /**
     * Delete wishlist by id
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws NotFoundException
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $resultRedirect->setPath('*/');
        }

        try {
            $wishlist = $this->wishlistProvider->getWishlist();
            if (!$wishlist) {
                throw new NotFoundException(__('Page not found.'));
            }
            if ($this->_objectManager->get(
                \Magento\MultipleWishlist\Helper\Data::class
            )->isWishlistDefault($wishlist)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __("The default wish list can't be deleted.")
                );
            }
            $wishlist->delete();
            $this->_objectManager->get(\Magento\Wishlist\Helper\Data::class)->calculate();
            $this->messageManager->addSuccess(
                __(
                    'Wish List "%1" has been deleted.',
                    $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($wishlist->getName())
                )
            );
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $message = __('We can\'t delete the wish list right now.');
            $this->messageManager->addException($e, $message);
        }
        return $resultRedirect->setPath('*/');
    }
}
