<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MultipleWishlist\Controller\Index;

use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\App\Action\Context;
use Magento\MultipleWishlist\Model\ItemManager;
use Magento\Wishlist\Controller\WishlistProviderInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Wishlist\Model\ItemFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Copyitems extends \Magento\MultipleWishlist\Controller\AbstractIndex
{
    /**
     * @var WishlistProviderInterface
     */
    protected $wishlistProvider;

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
     * @param ItemManager $itemManager
     * @param ItemFactory $itemFactory
     * @param Validator $formKeyValidator
     */
    public function __construct(
        Context $context,
        WishlistProviderInterface $wishlistProvider,
        ItemManager $itemManager,
        ItemFactory $itemFactory,
        Validator $formKeyValidator
    ) {
        $this->wishlistProvider = $wishlistProvider;
        $this->itemManager = $itemManager;
        $this->itemFactory = $itemFactory;
        $this->formKeyValidator = $formKeyValidator;
        parent::__construct($context);
    }

    /**
     * Copy wishlist items to given wishlist
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
        $notFound = [];
        $alreadyPresent = [];
        $failed = [];
        $copied = [];
        if (count($itemIds)) {
            $qtys = $this->getRequest()->getParam('qty', []);
            foreach ($itemIds as $id => $value) {
                try {
                    /* @var \Magento\Wishlist\Model\Item $item */
                    $item = $this->itemFactory->create();
                    $item->loadWithOptions($id);

                    $this->itemManager->copy($item, $wishlist, isset($qtys[$id]) ? $qtys[$id] : null);
                    $copied[$id] = $item;
                } catch (\InvalidArgumentException $e) {
                    $notFound[] = $id;
                } catch (\DomainException $e) {
                    $alreadyPresent[$id] = $item;
                } catch (\Exception $e) {
                    $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
                    $failed[] = $id;
                }
            }
        }
        $wishlistName = $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($wishlist->getName());

        $wishlist->save();

        if (count($notFound)) {
            $this->messageManager->addError(__('We can\'t find %1 items.', count($notFound)));
        }

        if (count($failed)) {
            $this->messageManager->addError(__('We can\'t copy %1 items.', count($failed)));
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

        if (count($copied)) {
            $this->_objectManager->get(\Magento\Wishlist\Helper\Data::class)->calculate();
            $names = $this->_objectManager->get(
                \Magento\Framework\Escaper::class
            )->escapeHtml($this->joinProductNames($copied));
            $this->messageManager->addSuccess(
                __('%1 items were copied to %2: %3.', count($copied), $wishlistName, $names)
            );
        }
        $resultRedirect->setUrl($this->_redirect->getRedirectUrl());
        return $resultRedirect;
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
}
