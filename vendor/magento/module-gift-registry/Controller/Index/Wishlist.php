<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Controller\Index;

use Magento\Catalog\Model\Product\Exception as ProductException;

class Wishlist extends \Magento\GiftRegistry\Controller\Index
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        parent::__construct($context, $coreRegistry, $formKeyValidator);
        $this->productRepository = $productRepository;
    }

    /**
     * Add wishlist items to customer active gift registry action
     *
     * @return void
     */
    public function execute()
    {
        $itemId = $this->getRequest()->getParam('item');
        $redirectParams = [];
        if ($itemId) {
            try {
                $entity = $this->_initEntity('entity');
                $wishlistItem = $this->_objectManager->create(
                    \Magento\Wishlist\Model\Item::class
                )->loadWithOptions(
                    $itemId,
                    'info_buyRequest'
                );
                $entity->addItem($wishlistItem->getProductId(), $wishlistItem->getBuyRequest());
                $this->messageManager->addSuccess(__('The wish list item has been added to this gift registry.'));
                $redirectParams['wishlist_id'] = $wishlistItem->getWishlistId();
            } catch (ProductException $e) {
                $product = $this->productRepository->getById((int)$wishlistItem->getProductId());
                $query['options'] = \Magento\GiftRegistry\Block\Product\View::FLAG;
                $query['entity'] = $this->getRequest()->getParam('entity');
                $this->getResponse()->setRedirect(
                    $product->getUrlModel()->getUrl($product, ['_query' => $query])
                );
                return;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_redirect('giftregistry');
                return;
            } catch (\Exception $e) {
                $this->messageManager->addError(__("We couldn’t add your wish list items to your gift registry."));
            }
        }

        $this->_redirect('wishlist', $redirectParams);
    }
}
