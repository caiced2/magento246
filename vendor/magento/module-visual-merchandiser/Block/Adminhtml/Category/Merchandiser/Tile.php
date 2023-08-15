<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Block\Adminhtml\Category\Merchandiser;

/**
 * Tile view block.
 *
 * @api
 * @since 100.1.0
 */
class Tile extends \Magento\Backend\Block\Widget\Grid
{
    const XML_PATH_ADDITIONAL_ATTRIBUTES = 'visualmerchandiser/options/product_attributes';
    const IMAGE_WIDTH = 130;
    const IMAGE_HEIGHT = 130;

    /**
     * @var string
     * @since 100.1.0
     */
    protected $_template = 'category/merchandiser/tile.phtml';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     * @since 100.1.0
     */
    protected $_coreRegistry = null;

    /**
     * Collection object
     *
     * @var \Magento\Framework\Data\Collection
     * @since 100.1.0
     */
    protected $_collection;

    /**
     * Catalog image
     *
     * @var \Magento\Catalog\Helper\Image
     * @since 100.1.0
     */
    protected $_catalogImage = null;

    /**
     * @var \Magento\VisualMerchandiser\Model\Category\Products
     * @since 100.1.0
     */
    protected $_products;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     * @since 100.1.0
     */
    protected $scopeConfig;

    /**
     * @var array
     * @since 100.1.0
     */
    protected $usableAttributes = null;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     * @since 100.1.0
     */
    protected $attributeFactory;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Catalog\Helper\Image $catalogImage
     * @param \Magento\VisualMerchandiser\Model\Category\Products $products
     * @param \Magento\VisualMerchandiser\Block\Adminhtml\Widget\Tile\Attribute\Factory $attributeFactory
     * @param array $data
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Catalog\Helper\Image $catalogImage,
        \Magento\VisualMerchandiser\Model\Category\Products $products,
        \Magento\VisualMerchandiser\Block\Adminhtml\Widget\Tile\Attribute\Factory $attributeFactory,
        array $data = [],
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency = null
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_catalogImage = $catalogImage;
        $this->_products = $products;
        $this->scopeConfig = $context->getScopeConfig();
        $this->attributeFactory = $attributeFactory;
        $this->priceCurrency = $priceCurrency ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magento\Framework\Pricing\PriceCurrencyInterface::class
        );
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setDefaultSort('position');
        $this->setDefaultDir('asc');
        $this->setUseAjax(true);
    }

    /**
     * Get image helper.
     *
     * @return \Magento\Catalog\Helper\Image
     * @since 100.1.0
     */
    public function getImageHelper()
    {
        return $this->_catalogImage;
    }

    /**
     * Retrieve product image url.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     * @since 100.1.0
     */
    public function getImageUrl($product)
    {
        $image = $this->getImageHelper()
            ->init($product, 'small_image', ['type' => 'small_image'])
            ->resize(self::IMAGE_WIDTH, self::IMAGE_HEIGHT);
        return $image->getUrl();
    }

    /**
     * Initialize grid
     *
     * @return void
     * @since 100.1.0
     */
    protected function _prepareGrid()
    {
        $this->_prepareCollection();
    }

    /**
     * Retrieve position cache key.
     *
     * @return string
     * @since 100.1.0
     */
    protected function _getPositionCacheKey()
    {
        return $this->getPositionCacheKey() ?? $this->getParentBlock()->getPositionCacheKey();
    }

    /**
     * Prepare product collection, set product position.
     *
     * @return $this
     * @since 100.1.0
     */
    protected function _prepareCollection()
    {
        $this->_products->setCacheKey($this->_getPositionCacheKey());
        $productPositions = $this->_backendSession->getCategoryProductPositions(true);
        $collection = $this->_products->getCollectionForGrid(
            (int) $this->getRequest()->getParam('id', 0),
            (int) $this->getRequest()->getParam('store'),
            $productPositions
        );

        $collection->clear();
        $this->setCollection($collection);

        $this->_preparePage();

        $idx = ($collection->getCurPage() * $collection->getPageSize()) - $collection->getPageSize();
        $collection->getSelect()->group('e.entity_id');
        foreach ($collection as $item) {
            $item->setPosition($idx);
            $idx++;
        }

        $this->_products->setCacheKey($this->_getPositionCacheKey());
        $this->_products->savePositions($collection);

        return $this;
    }

    /**
     * Set collection object
     *
     * @param \Magento\Framework\Data\Collection $collection
     * @return void
     * @since 100.1.0
     */
    public function setCollection($collection)
    {
        $this->_collection = $collection;
    }

    /**
     * Get collection object
     *
     * @return \Magento\Framework\Data\Collection
     * @since 100.1.0
     */
    public function getCollection()
    {
        return $this->_collection;
    }

    /**
     * Retrieve column by id
     *
     * @param string $columnId
     * @return \Magento\Framework\View\Element\AbstractBlock|bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @since 100.1.0
     */
    public function getColumn($columnId)
    {
        return false;
    }

    /**
     * Retrieve list of grid columns
     *
     * @return array
     * @since 100.1.0
     */
    public function getColumns()
    {
        return [];
    }

    /**
     * Process column filtration values
     *
     * @param mixed $data
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @since 100.1.0
     */
    protected function _setFilterValues($data)
    {
        return $this;
    }

    /**
     * Get category.
     *
     * @return array|null
     * @since 100.1.0
     */
    public function getCategory()
    {
        return $this->_coreRegistry->registry('category');
    }

    /**
     * Retrieve grid url.
     *
     * @return string
     * @since 100.1.0
     */
    public function getGridUrl()
    {
        return $this->getUrl('merchandiser/*/tile', ['_current' => true]);
    }

    /**
     * Retrieve additional product attributes.
     *
     * @return array
     * @since 100.1.0
     */
    protected function getUsableAttributes()
    {
        if ($this->usableAttributes == null) {
            $attributeCodes = (string) $this->scopeConfig->getValue(self::XML_PATH_ADDITIONAL_ATTRIBUTES);
            $attributeCodes = explode(',', $attributeCodes);
            $this->usableAttributes = array_map('trim', $attributeCodes);
        }
        return $this->usableAttributes;
    }

    /**
     * Retrieve product attributes to be displayed in tile view.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     * @since 100.1.0
     */
    public function getAttributesToDisplay($product)
    {
        $attributeCodes = $this->getUsableAttributes();
        $availableAttributes = $product->getTypeInstance()->getSetAttributes($product);
        $availableFields = array_keys($product->getData());
        $filteredAttributes = [];

        foreach ($attributeCodes as $code) {
            $renderer = $this->attributeFactory->create($code);

            if ($code == 'price') {
                $attributeObject = $availableAttributes[$code];
                $filteredAttributes[] = $renderer->addData(
                    [
                        'label' => $attributeObject->getFrontend()->getLabel(),
                        'value' => $this->priceCurrency->format($product->getPrice())
                    ]
                );
            } elseif (isset($availableAttributes[$code])) {
                $attributeObject = $availableAttributes[$code];
                $filteredAttributes[] = $renderer->addData(
                    [
                        'label' => $attributeObject->getFrontend()->getLabel(),
                        'value' => $product->getData($code)
                    ]
                );
            } elseif (in_array($code, $availableFields)) {
                $filteredAttributes[] = $renderer->addData(
                    [
                        'label' => ucwords($code),
                        'value' => $product->getData($code)
                    ]
                );
            }
        }

        return $filteredAttributes;
    }
}
