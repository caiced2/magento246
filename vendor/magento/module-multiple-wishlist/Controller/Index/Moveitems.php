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
use Magento\Framework\Exception\NotFoundException;
use Magento\MultipleWishlist\Model\ItemManager;
use Magento\Wishlist\Controller\WishlistProviderInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Wishlist\Model\ItemFactory;
use Magento\Wishlist\Model\ResourceModel\Wishlist\CollectionFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Moveitems extends \Magento\MultipleWishlist\Controller\AbstractIndex
{
    /**
     * @var ItemFactory
     */
    protected $itemFactory;

    /**
     * @var CollectionFactory
     */
    protected $wishlistColFactory;

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
     * @var Validator
     */
    protected $formKeyValidator;

    /**
     * @param Context $context
     * @param ItemFactory $itemFactory
     * @param CollectionFactory $wishlistColFactory
     * @param WishlistProviderInterface $wishlistProvider
     * @param Session $customerSession
     * @param ItemManager $itemManager
     * @param Validator $formKeyValidator
     */
    public function __construct(
        Context $context,
        ItemFactory $itemFactory,
        CollectionFactory $wishlistColFactory,
        WishlistProviderInterface $wishlistProvider,
        Session $customerSession,
        ItemManager $itemManager,
        Validator $formKeyValidator
    ) {
        $this->itemFactory = $itemFactory;
        $this->wishlistColFactory = $wishlistColFactory;
        $this->wishlistProvider = $wishlistProvider;
        $this->customerSession = $customerSession;
        $this->itemManager = $itemManager;
        $this->formKeyValidator = $formKeyValidator;
        parent::__construct($context);
    }

    /**
     * Join item product names
     *
     * @param array $items
     * @return string
     */
    protected function joinProductNames($items)
    {
        return join(
            ', ',
            array_map(
                function ($item) {
                    return '"' . $item->getProduct()->getName() . '"';
                },
                $items
            )
        );
    }

    /**
     * Move wishlist items to given wishlist
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws NotFoundException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $resultRedirect->setPath('*/');
        }

        $wishlist = $this->wishlistProvider->getWishlist();
        if (!$wishlist) {
            throw new NotFoundException(__('Page not found.'));
        }
        $itemIds = $this->getRequest()->getParam('selected', []);
        $moved = [];
        $failed = [];
        $notFound = [];
        $notAllowed = [];
        $alreadyPresent = [];
        if (count($itemIds)) {
            /** @var \Magento\Wishlist\Model\ResourceModel\Wishlist\Collection $wishlists */
            $wishlists = $this->wishlistColFactory->create();
            $wishlists->filterByCustomerId($this->customerSession->getCustomerId());
            $qtys = $this->getRequest()->getParam('qty', []);

            foreach ($itemIds as $id => $value) {
                try {
                    /* @var \Magento\Wishlist\Model\Item $item */
                    $item = $this->itemFactory->create();
                    $item->loadWithOptions($id);

                    $this->itemManager->move($item, $wishlist, $wishlists, isset($qtys[$id]) ? $qtys[$id] : null);
                    $moved[$id] = $item;
                } catch (\InvalidArgumentException $e) {
                    $notFound[] = $id;
                } catch (\DomainException $e) {
                    if ($e->getCode() == 1) {
                        $alreadyPresent[$id] = $item;
                    } else {
                        $notAllowed[$id] = $item;
                    }
                } catch (\Exception $e) {
                    $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
                    $failed[] = $id;
                }
            }
        }

        $wishlistName = $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($wishlist->getName());

        if (count($notFound)) {
            $this->messageManager->addError(__('We can\'t find %1 items.', count($notFound)));
        }

        if (count($notAllowed)) {
            $names = $this->_objectManager->get(
                \Magento\Framework\Escaper::class
            )->escapeHtml($this->joinProductNames($notAllowed));
            $this->messageManager->addError(__('%1 items cannot be moved: %2.', count($notAllowed), $names));
        }

        if (count($alreadyPresent)) {
            $names = $this->_objectManager->get(
                \Magento\Framework\Escaper::class
            )->escapeHtml(
                $this->joinProductNames($alreadyPresent)
            );
            $this->messageManager->addError(
                __('%1 items are already present in %2: %3.', count($alreadyPresent), $wishlistName, $names)
            );
        }

        if (count($failed)) {
            $this->messageManager->addError(__('We can\'t move %1 items.', count($failed)));
        }

        if (count($moved)) {
            $this->_objectManager->get(\Magento\Wishlist\Helper\Data::class)->calculate();
            $names = $this->_objectManager->get(
                \Magento\Framework\Escaper::class
            )->escapeHtml($this->joinProductNames($moved));
            $this->messageManager->addSuccess(
                __('%1 items were moved to %2: %3.', count($moved), $wishlistName, $names)
            );
        }
        $resultRedirect->setUrl($this->_redirect->getRedirectUrl());
        return $resultRedirect;
    }
}
