<?php
/**
 * Wishlist item report collection
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MultipleWishlist\Model\ResourceModel\Item\Report;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\ObjectManager;

/**
 * Resource model for wishlist items collection.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Catalog data
     *
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * Wishlist data
     *
     * @var \Magento\Wishlist\Helper\Data
     */
    protected $_wishlistData = null;

    /**
     * @var \Magento\Framework\DataObject\Copy\Config
     */
    protected $_fieldsetConfig;

    /**
     * Customer resource model
     *
     * @var \Magento\Customer\Model\ResourceModel\Customer
     */
    protected $_resourceCustomer;

    /**
     * @var ProductCollectionFactory|null
     */
    private $productCollectionFactory;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Wishlist\Helper\Data $wishlistData
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\DataObject\Copy\Config $fieldsetConfig
     * @param \Magento\Customer\Model\ResourceModel\Customer $resourceCustomer
     * @param mixed $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource
     * @param ProductCollectionFactory $productCollectionFactory
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Wishlist\Helper\Data $wishlistData,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\DataObject\Copy\Config $fieldsetConfig,
        \Magento\Customer\Model\ResourceModel\Customer $resourceCustomer,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null,
        ProductCollectionFactory $productCollectionFactory = null
    ) {
        $this->_wishlistData = $wishlistData;
        $this->moduleManager = $moduleManager;
        $this->_fieldsetConfig = $fieldsetConfig;
        $this->_resourceCustomer = $resourceCustomer;
        $this->productCollectionFactory = $productCollectionFactory ?: ObjectManager::getInstance()
            ->get(ProductCollectionFactory::class);
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    /**
     * Init model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Magento\MultipleWishlist\Model\Item::class,
            \Magento\MultipleWishlist\Model\ResourceModel\Item::class
        );
    }

    /**
     * Add customer information to collection items
     *
     * @return $this
     */
    protected function _addCustomerInfo()
    {
        $customerAccount = $this->_fieldsetConfig->getFieldset('customer_account');

        foreach ($customerAccount as $code => $field) {
            if (isset($field['name'])) {
                $fields[$code] = $code;
            }
        }

        $connection = $this->getConnection();
        $this->getSelect()->joinLeft(
            ['customer' => $this->getTable('customer_entity')],
            'customer.entity_id = wishlist_table.customer_id',
            []
        );

        $concatenate = [];
        if (isset($fields['prefix'])) {
            $fields['prefix'] = 'customer.prefix';
            $concatenate[] = $connection->getCheckSql(
                '{{prefix}} IS NOT NULL AND {{prefix}} != \'\'',
                $connection->getConcatSql(['LTRIM(RTRIM({{prefix}}))', '\' \'']),
                '\'\''
            );
        }
        $fields['firstname'] = 'customer.firstname';
        $concatenate[] = 'LTRIM(RTRIM({{firstname}}))';
        $concatenate[] = '\' \'';
        if (isset($fields['middlename'])) {
            $fields['middlename'] = 'customer.middlename';
            $concatenate[] = $connection->getCheckSql(
                '{{middlename}} IS NOT NULL AND {{middlename}} != \'\'',
                $connection->getConcatSql(['LTRIM(RTRIM({{middlename}}))', '\' \'']),
                '\'\''
            );
        }
        $fields['lastname'] = 'customer.lastname';
        $concatenate[] = 'LTRIM(RTRIM({{lastname}}))';
        if (isset($fields['suffix'])) {
            $fields['suffix'] = 'customer.suffix';
            $concatenate[] = $connection->getCheckSql(
                '{{suffix}} IS NOT NULL AND {{suffix}} != \'\'',
                $connection->getConcatSql(['\' \'', 'LTRIM(RTRIM({{suffix}}))']),
                '\'\''
            );
        }

        $nameExpr = $connection->getConcatSql($concatenate);

        $this->addExpressionFieldToSelect('customer_name', $nameExpr, $fields);

        return $this;
    }

    /**
     * Filter collection by store ids
     *
     * @param array $storeIds
     * @return $this
     */
    public function filterByStoreIds(array $storeIds)
    {
        $this->addFieldToFilter('main_table.store_id', ['in' => [$storeIds]]);
        return $this;
    }

    /**
     * Add product information to collection
     *
     * @return $this
     */
    protected function _addProductInfo()
    {
        if ($this->moduleManager->isEnabled('Magento_CatalogInventory')) {
            $this->getSelect()->joinLeft(
                ['item_stock' => $this->getTable('cataloginventory_stock_item')],
                'main_table.product_id = item_stock.product_id',
                ['product_qty' => 'qty']
            );
            $this->getSelect()->columns(['qty_diff' => '(item_stock.qty - main_table.qty)']);
            $this->addFilterToMap('product_qty', 'item_stock.qty');
            $this->addFilterToMap('qty_diff', '(item_stock.qty - main_table.qty)');
        }

        return $this;
    }

    /**
     * Add selected data
     *
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $select = $this->getSelect();
        $select->reset(
            \Magento\Framework\DB\Select::COLUMNS
        )->columns(
            ['item_qty' => 'qty', 'added_at', 'description', 'product_id']
        );

        $connection = $this->getSelect()->getConnection();
        $defaultWishlistName = $this->_wishlistData->getDefaultWishlistName();
        $this->getSelect()->join(
            ['wishlist_table' => $this->getTable('wishlist')],
            'main_table.wishlist_id = wishlist_table.wishlist_id',
            [
                'visibility' => 'visibility',
                'wishlist_name' => $connection->getIfNullSql('name', $connection->quote($defaultWishlistName))
            ]
        );

        $this->addFilterToMap(
            'wishlist_name',
            $connection->getIfNullSql('name', $connection->quote($defaultWishlistName))
        );
        $this->addFilterToMap('item_qty', 'main_table.qty');
        $this->_addCustomerInfo();
        $this->_addProductInfo();

        return $this;
    }

    /**
     * Add product info to collection
     *
     * @return void
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();

        $productIds = $this->getColumnValues('product_id');
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addAttributeToSelect(['name', 'price', 'sku'])
            ->addFieldToFilter('entity_id', ['in' => $productIds]);

        foreach ($this->_items as $item) {
            $product = $productCollection->getItemById($item->getProductId());
            /* @var $item \Magento\MultipleWishlist\Model\Item $item*/
            $item->setProductName($product->getName());
            $item->setProductPrice($product->getPrice());
            $item->setProductSku($product->getSku());
        }
    }
}
