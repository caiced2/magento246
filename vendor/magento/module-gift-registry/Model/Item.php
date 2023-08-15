<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Model;

use Magento\CatalogInventory\Model\StockStateException;
use Magento\Framework\Exception\LocalizedException;

/**
 * Entity items data model
 *
 * @method \Magento\GiftRegistry\Model\Item setEntityId(int $value)
 * @method int getProductId()
 * @method \Magento\GiftRegistry\Model\Item setProductId(int $value)
 * @method float getQty()
 * @method float getQtyFulfilled()
 * @method \Magento\GiftRegistry\Model\Item setQtyFulfilled(float $value)
 * @method string getNote()
 * @method \Magento\GiftRegistry\Model\Item setNote(string $value)
 * @method string getAddedAt()
 * @method \Magento\GiftRegistry\Model\Item setAddedAt(string $value)
 * @method string getCustomOptions()
 * @method \Magento\GiftRegistry\Model\Item setCustomOptions(string $value)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @api
 * @since 100.0.2
 */
class Item extends \Magento\Framework\Model\AbstractModel implements
    \Magento\Catalog\Model\Product\Configuration\Item\ItemInterface
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\GiftRegistry\Model\Item\OptionFactory
     */
    protected $optionFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * List of options related to item
     *
     * @var array
     */
    protected $_options = [];

    /**
     * Assoc array of item options
     * Option codes are used as array keys
     *
     * @var array
     */
    protected $_optionsByCode = [];

    /**
     * @var array|\Magento\Catalog\Model\ResourceModel\Url
     */
    protected $resourceUrl = [];

    /**
     * Serializer interface instance.
     *
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param Item\OptionFactory $optionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Url $resourceUrl
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\GiftRegistry\Model\Item\OptionFactory $optionFactory,
        \Magento\Catalog\Model\ResourceModel\Url $resourceUrl,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        \Magento\Framework\Serialize\Serializer\Json $serializer = null
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->productRepository = $productRepository;
        $this->optionFactory = $optionFactory;
        $this->optionFactory = $optionFactory;
        $this->resourceUrl = $resourceUrl;
        $this->messageManager = $messageManager;
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\Json::class);
    }

    /**
     * Flag stating that options were successfully saved
     *
     * @var bool
     */
    protected $_flagOptionsSaved = null;

    /**
     * Model construct that should be used for object initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\GiftRegistry\Model\ResourceModel\Item::class);
    }

    /**
     * Load item by registry id and product id
     *
     * @param int $registryId
     * @param int $productId
     * @return $this
     */
    public function loadByProductRegistry($registryId, $productId)
    {
        $this->_getResource()->loadByProductRegistry($this, $registryId, $productId);
        return $this;
    }

    /**
     * Add or Move item product to shopping cart
     *
     * Return true if product was successful added or exception with code
     * Return false for disabled or unvisible products
     *
     * @param \Magento\Checkout\Model\Cart $cart
     * @param int $qty
     * @return bool
     * @throws StockStateException
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function addToCart(\Magento\Checkout\Model\Cart $cart, $qty)
    {
        $product = $this->_getProduct();
        $storeId = $this->getStoreId();

        if ($this->getQty() < $qty + $this->getQtyFulfilled()) {
            $qty = $this->getQty() - $this->getQtyFulfilled();
            $this->messageManager->addNotice(
                __(
                    'The quantity of "%1" product added to cart exceeds the quantity desired by the'
                    .' Gift Registry owner. The quantity added has been adjusted to meet remaining quantity %2.',
                    $product->getName(),
                    $qty
                )
            );
        }

        $productIdsInCart = $cart->getProductIds();
        if (in_array($product->getId(), $productIdsInCart)) {
            foreach ($cart->getQuote()->getAllItems() as $item) {
                if (($item->getProduct()->getId() == $product->getId())
                    /*
                     * Checkout of giftRegistry products together
                     * with non-registry products will be adjusted in a specific story
                     */
                    /*&& ($item->getGiftregistryItemId() == $this->getId())*/
                    && (($item->getQty() + $qty) > ($this->getQty() - $this->getQtyFulfilled()))) {
                    $cart->removeItem($item->getId());
                    $this->messageManager->addNotice(
                        __(
                            'Existing quantity of "%1" product in the cart has been replaced '
                            .'with quantity %2 just requested.',
                            $product->getName(),
                            $qty
                        )
                    );
                }
            }
        }

        if ($product->getStatus() != \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED) {
            return false;
        }

        if (!$product->isVisibleInSiteVisibility()) {
            if ($product->getStoreId() == $storeId) {
                return false;
            }
            $urlData = $this->resourceUrl->getRewriteByProductStore([$product->getId() => $storeId]);
            if (!isset($urlData[$product->getId()])) {
                return false;
            }
            $product->setUrlDataObject(new \Magento\Framework\DataObject($urlData));
            $visibility = $product->getUrlDataObject()->getVisibility();
            if (!in_array($visibility, $product->getVisibleInSiteVisibilities())) {
                return false;
            }
        }

        if (!$product->isSalable()) {
            throw new StockStateException(__('This product(s) is out of stock.'));
        }

        $product->setGiftregistryItemId($this->getId());
        $product->addCustomOption('giftregistry_id', $this->getEntityId());
        $request = $this->getBuyRequest();
        $request->setQty($qty);

        $cart->addProduct($product, $request);
        $relatedProduct = $request->getRelatedProduct();
        if (!empty($relatedProduct)) {
            $cart->addProductsByIds(explode(',', $relatedProduct));
        }

        if (!$product->isVisibleInSiteVisibility()) {
            $cart->getQuote()->getItemByProduct($product)->setStoreId($storeId);
        }

        return true;
    }

    /**
     * Check product representation in item
     *
     * @param   \Magento\Catalog\Model\Product $product
     * @return  bool
     */
    public function isRepresentProduct($product)
    {
        if ($this->getProductId() != $product->getId()) {
            return false;
        }

        $itemOptions = $this->getOptionsByCode();
        $productOptions = $product->getCustomOptions();

        if (!$this->_compareOptions($itemOptions, $productOptions)) {
            return false;
        }
        if (!$this->_compareOptions($productOptions, $itemOptions)) {
            return false;
        }
        return true;
    }

    /**
     * Check if two option sets are identical
     *
     * @param array $options1
     * @param array $options2
     * @return bool
     */
    protected function _compareOptions($options1, $options2)
    {
        $skipOptions = ['qty', 'info_buyRequest'];
        foreach ($options1 as $option) {
            $code = $option->getCode();
            if (in_array($code, $skipOptions)) {
                continue;
            }
            if (!isset(
                $options2[$code]
            ) || $options2[$code]->getValue() === null || $options2[$code]->getValue() != $option->getValue()
            ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Set product attributes to item
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return $this
     */
    public function setProduct($product)
    {
        $this->setName($product->getName());
        $this->setData('product', $product);
        return $this;
    }

    /**
     * Return product url
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     * @codeCoverageIgnore
     */
    public function getProductUrl()
    {
        return $this->getProduct()->getProductUrl();
    }

    /**
     * Return item product
     *
     * @return \Magento\Catalog\Model\Product
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getProduct()
    {
        if (!$this->_getData('product')) {
            try {
                $product = $this->productRepository->getById($this->getProductId());
            } catch (\Magento\Framework\Exception\NoSuchEntityException $noEntityException) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Please correct the product for adding the item to the quote.'),
                    $noEntityException
                );
            }
            $this->setProduct($product);
        }
        return $this->_getData('product');
    }

    /**
     * Return item product
     *
     * @return \Magento\Catalog\Model\Product
     * @codeCoverageIgnore
     */
    public function getProduct()
    {
        return $this->_getProduct();
    }

    /**
     * Checks if item model has data changes
     *
     * @return boolean
     */
    protected function _hasModelChanged()
    {
        if (!$this->hasDataChanges()) {
            return false;
        }

        return $this->_getResource()->hasDataChanged($this);
    }

    /**
     * Save item options after item is saved
     *
     * @return $this
     */
    public function afterSave()
    {
        $this->saveItemOptions();
        return parent::afterSave();
    }

    /**
     * Save item options
     *
     * @return $this
     */
    public function saveItemOptions()
    {
        foreach ($this->_options as $index => $option) {
            if ($option->isDeleted()) {
                $option->delete();
                unset($this->_options[$index]);
                unset($this->_optionsByCode[$option->getCode()]);
            } else {
                $option->save();
            }
        }

        $this->_flagOptionsSaved = true;
        // Report to watchers that options were saved

        return $this;
    }

    /**
     * Mark option save requirement
     *
     * @param bool $flag
     * @return void
     * @codeCoverageIgnore
     */
    public function setIsOptionsSaved($flag)
    {
        $this->_flagOptionsSaved = $flag;
    }

    /**
     * Were options saved?
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isOptionsSaved()
    {
        return $this->_flagOptionsSaved;
    }

    /**
     * Initialize item options
     *
     * @param array $options
     * @return $this
     */
    public function setOptions($options)
    {
        foreach ($options as $option) {
            $this->addOption($option);
        }
        return $this;
    }

    /**
     * Retrieve all item options
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Retrieve all item options as assoc array with option codes as array keys
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getOptionsByCode()
    {
        return $this->_optionsByCode;
    }

    /**
     * Remove option from item options
     *
     * @param string $code
     * @return $this
     */
    public function removeOption($code)
    {
        $option = $this->getOptionByCode($code);
        if ($option) {
            $option->isDeleted(true);
        }
        return $this;
    }

    /**
     * Add option to item
     *
     * @param \Magento\GiftRegistry\Model\Item\Option $option
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addOption($option)
    {
        if (is_array($option)) {
            $option = $this->optionFactory->create()->setData($option)->setItem($this);
        } elseif ($option instanceof \Magento\Quote\Model\Quote\Item\Option) {
            // import data from existing quote item option
            $option = $this->optionFactory->create()->setProduct(
                $option->getProduct()
            )->setCode(
                $option->getCode()
            )->setValue(
                $option->getValue()
            )->setItem(
                $this
            );
        } elseif ($option instanceof \Magento\Framework\DataObject
            && !$option instanceof \Magento\GiftRegistry\Model\Item\Option
        ) {
            $option = $this->optionFactory->create()->setData(
                $option->getData()
            )->setProduct(
                $option->getProduct()
            )->setItem(
                $this
            );
        } elseif ($option instanceof \Magento\GiftRegistry\Model\Item\Option) {
            $option->setItem($this);
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__('Please correct the item option format.'));
        }

        $exOption = $this->getOptionByCode($option->getCode());
        if ($exOption !== null) {
            $exOption->addData($option->getData());
        } else {
            $this->_addOptionCode($option);
            $this->_options[] = $option;
        }
        return $this;
    }

    /**
     * Register option code
     *
     * @param \Magento\GiftRegistry\Model\Item\Option $option
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _addOptionCode($option)
    {
        if (!isset($this->_optionsByCode[$option->getCode()])) {
            $this->_optionsByCode[$option->getCode()] = $option;
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('An item option with code %1 already exists.', $option->getCode())
            );
        }
        return $this;
    }

    /**
     * Retrieve item option by code
     *
     * @param   string $code
     * @return  \Magento\GiftRegistry\Model\Item\Option|null
     */
    public function getOptionByCode($code)
    {
        if (isset($this->_optionsByCode[$code]) && !$this->_optionsByCode[$code]->isDeleted()) {
            return $this->_optionsByCode[$code];
        }
        return null;
    }

    /**
     * Get formatted buy request object holding request received from product page with configured product keys/options
     *
     * @return \Magento\Framework\DataObject
     */
    public function getBuyRequest()
    {
        $option = $this->getOptionByCode('info_buyRequest');
        $data = $option ? $this->serializer->unserialize($option->getValue()) : [];
        $buyRequest = new \Magento\Framework\DataObject($data);
        $buyRequest->setOriginalQty($buyRequest->getQty())->setQty($this->getQty() * 1);
        // Qty value that is stored in buyRequest can be out-of-date
        return $buyRequest;
    }

    /**
     * Clone gift registry item
     *
     * @return $this
     */
    public function __clone()
    {
        $options = $this->getOptions();
        $this->_options = [];
        $this->_optionsByCode = [];
        foreach ($options as $option) {
            $this->addOption(clone $option);
        }
        return $this;
    }

    /**
     * Returns special download params (if needed) for custom option with type = 'file'
     * Needed to implement \Magento\Catalog\Model\Product\Configuration\Item\Interface.
     * Currently returns null, as far as we don't show file options and don't need controllers to give file.
     *
     * @return null|\Magento\Framework\DataObject
     * @codeCoverageIgnore
     */
    public function getFileDownloadParams()
    {
        return null;
    }

    /**
     * Validates and sets quantity for the related product
     *
     * @param int|float $quantity New item quantity
     * @return $this
     */
    public function setQty($quantity)
    {
        $quantity = (double)$quantity;

        if (!$this->_getProduct()->getTypeInstance()->canUseQtyDecimals()) {
            $quantity = round($quantity);
        }

        if ($quantity <= 0) {
            $quantity = 1;
        }

        return $this->setData('qty', $quantity);
    }
}
