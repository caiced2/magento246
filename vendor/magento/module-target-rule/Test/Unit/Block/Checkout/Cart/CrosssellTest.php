<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Block\Checkout\Cart;

use ArrayIterator;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Config;
use Magento\Catalog\Model\Product\Link;
use Magento\Catalog\Model\Product\LinkFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Link\Product\Collection;
use Magento\CatalogInventory\Model\Stock\Status;
use Magento\Checkout\Model\Session;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TargetRule\Block\Checkout\Cart\Crosssell;
use Magento\TargetRule\Helper\Data;
use Magento\TargetRule\Model\Index;
use Magento\TargetRule\Model\IndexFactory;
use Magento\TargetRule\Model\Rotation;
use Magento\TargetRule\Model\Rule;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CrosssellTest extends TestCase
{
    /** @var Crosssell */
    private $crosssell;

    /** @var Data|MockObject */
    private $targetRuleHelper;

    /** @var MockObject */
    private $linkFactory;

    /** @var StoreManagerInterface|MockObject */
    private $storeManager;

    /**
     * @var Session|MockObject
     */
    private $checkoutSession;

    /**
     * @var IndexFactory|MockObject
     */
    private $indexFactory;

    /**
     * @var CollectionFactory|MockObject
     */
    private $collectionFactory;

    /**
     * @var \Magento\TargetRule\Model\ResourceModel\Index|MockObject
     */
    private $index;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->checkoutSession =
            $this->getMockBuilder(Session::class)
                ->addMethods(['getLastAddedProductId'])
                ->onlyMethods(['getQuote'])
                ->disableOriginalConstructor()
                ->getMock();
        $this->indexFactory =
            $this->createPartialMock(IndexFactory::class, ['create']);
        $this->collectionFactory =
            $this->createPartialMock(CollectionFactory::class, ['create']);
        $this->index = $this->createMock(\Magento\TargetRule\Model\ResourceModel\Index::class);

        $this->storeManager = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $catalogConfig = $this->createMock(Config::class);
        $context = $this->createMock(Context::class);
        $context->expects($this->any())->method('getStoreManager')->willReturn($this->storeManager);
        $context->expects($this->any())->method('getCatalogConfig')->willReturn($catalogConfig);
        $this->targetRuleHelper = $this->createMock(Data::class);
        $visibility = $this->createMock(Visibility::class);
        $status = $this->createMock(Status::class);
        $this->linkFactory = $this->createPartialMock(LinkFactory::class, ['create']);
        $productFactory = $this->createPartialMock(ProductFactory::class, ['create']);
        $config = $this->getMockForAbstractClass(ConfigInterface::class);
        $objectManager = new ObjectManager($this);
        $rotation = $objectManager->getObject(Rotation::class);

        $this->crosssell = (new ObjectManager($this))->getObject(
            Crosssell::class,
            [
                'context' => $context,
                'index' => $this->index,
                'targetRuleData' => $this->targetRuleHelper,
                'productCollectionFactory' => $this->collectionFactory,
                'visibility' => $visibility,
                'status' => $status,
                'session' => $this->checkoutSession,
                'productLinkFactory' => $this->linkFactory,
                'productFactory' => $productFactory,
                'indexFactory' => $this->indexFactory,
                'productTypeConfig' => $config,
                'rotation' => $rotation
            ]
        );
    }

    /**
     * Test for getTargetLinkCollection.
     */
    public function testGetTargetLinkCollection(): void
    {
        $store = $this->createMock(Store::class);
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($store);
        $this->targetRuleHelper->expects($this->once())->method('getMaximumNumberOfProduct')
            ->with(Rule::CROSS_SELLS);
        $productCollection = $this->createMock(
            Collection::class
        );
        $productLinkCollection = $this->createMock(Link::class);
        $this->linkFactory->expects($this->once())->method('create')->willReturn($productLinkCollection);
        $productLinkCollection->expects($this->once())->method('useCrossSellLinks')->willReturnSelf();
        $productLinkCollection->expects($this->once())->method('getProductCollection')->willReturn($productCollection);
        $productCollection->expects($this->once())->method('setStoreId')->willReturnSelf();
        $productCollection->expects($this->once())->method('setPageSize')->willReturnSelf();
        $productCollection->expects($this->once())->method('setGroupBy')->willReturnSelf();
        $productCollection->expects($this->once())->method('addMinimalPrice')->willReturnSelf();
        $productCollection->expects($this->once())->method('addFinalPrice')->willReturnSelf();
        $productCollection->expects($this->once())->method('addTaxPercents')->willReturnSelf();
        $productCollection->expects($this->once())->method('addAttributeToSelect')->willReturnSelf();
        $productCollection->expects($this->once())->method('addUrlRewrite')->willReturnSelf();
        $select = $this->createMock(Select::class);
        $productCollection->expects($this->once())->method('getSelect')->willReturn($select);

        $this->assertSame($productCollection, $this->crosssell->getLinkCollection());
    }

    /**
     * Test for getItemCollection.
     *
     * @param int $limit
     * @param int $numberOfCrossSellProducts
     * @param int $numberOfLinkProducts
     * @param int $numberOfProductsInCart
     * @param int $expected
     * @dataProvider getItemCollectionDataProvider
     */
    public function testGetItemCollection(
        int $limit,
        int $numberOfCrossSellProducts,
        int $numberOfLinkProducts,
        int $numberOfProductsInCart,
        int $expected
    ): void {
        $this->storeManager->method('getStore')->willReturn(new DataObject(['id' => 1]));
        $this->targetRuleHelper->method('getRotationMode')
            ->with(Rule::CROSS_SELLS)
            ->willReturn(Rule::ROTATION_NONE);

        $quoteItems = [];
        foreach ($this->getProducts($this->generateRandomIds(1000, 1999, $numberOfProductsInCart)) as $product) {
            $quoteItems[] = new DataObject(['product' => $product]);
        }
        $quote = new DataObject(['all_items' => $quoteItems]);

        $this->checkoutSession->method('getQuote')->willReturn($quote);
        $this->checkoutSession->method('getLastAddedProductId')->willReturn(1);

        $targetRuleIndex = $this->getMockBuilder(Index::class)
            ->disableOriginalConstructor()
            ->setMethods(['setType', 'setLimit', 'setProduct', 'setExcludeProductIds', 'getProductIds'])
            ->getMock();
        $targetRuleIndex->method('setType')->willReturnSelf();
        $targetRuleIndex->method('setLimit')->willReturnSelf();
        $targetRuleIndex->method('setProduct')->willReturnSelf();
        $targetRuleIndex->method('setExcludeProductIds')->willReturnSelf();
        $targetRuleIndex->method('getProductIds')
            ->willReturn(array_flip($this->generateRandomIds(2000, 2999, $numberOfCrossSellProducts)));
        $linkCollection = $this->_getLinkCollection($this->generateRandomIds(3000, 3999, $numberOfLinkProducts));
        $this->linkFactory->method('create')->willReturn($linkCollection);

        $this->indexFactory->method('create')->willReturn($targetRuleIndex);

        $this->collectionFactory->method('create')->willReturn($this->_getProductCollection());

        $this->targetRuleHelper
            ->method('getMaximumNumberOfProduct')
            ->with(Rule::CROSS_SELLS)
            ->willReturn($limit);

        $this->assertCount($expected, $this->crosssell->getItemCollection());
    }

    /**
     * Data provider for test.
     *
     * @return array
     */
    public function getItemCollectionDataProvider(): array
    {
        return [
            'No link products' => [5, 6, 0, 1, 5],
            'Number of products found is less than limit' => [5, 4, 0, 1, 4],
            'Cross-sell and link products are both not empty' =>  [5, 4, 6, 1, 5],
            'Limit = 0' => [0, 4, 6, 1, 0],
            'Empty quote' => [5, 6, 0, 0, 0],
        ];
    }

    /**
     * Get product collection.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection|MockObject
     */
    private function _getProductCollection()
    {
        $productCollection = $this->createPartialMock(
            \Magento\Catalog\Model\ResourceModel\Product\Collection::class,
            [
                'addMinimalPrice',
                'addFinalPrice',
                'addTaxPercents',
                'addAttributeToSelect',
                'addUrlRewrite',
                'getStoreId',
                'addFieldToFilter',
                'isEnabledFlat',
                'setVisibility',
                'load',
                'getProductEntityMetadata'
            ]
        );
        $productCollection->method('addMinimalPrice')->willReturnSelf();
        $productCollection->method('addFinalPrice')->willReturnSelf();
        $productCollection->method('addTaxPercents')->willReturnSelf();
        $productCollection->method('addAttributeToSelect')->willReturnSelf();
        $productCollection->method('addUrlRewrite')->willReturnSelf();
        $productCollection->method('setVisibility')->willReturnSelf();
        $productCollection->method('getStoreId')->willReturn(1);
        $productCollection->method('isEnabledFlat')->willReturn(false);
        $entityMetadataInterface =$this->createMock(EntityMetadataInterface::class);
        $entityMetadataInterface->method('getLinkField')->willReturn('entity_id');
        $productCollection->method('getProductEntityMetadata')->willReturn($entityMetadataInterface);
        $productCollection
            ->method('addFieldToFilter')
            ->willReturnCallback(
                function ($field, $condition) use ($productCollection) {
                    if ($field == 'entity_id' && isset($condition['in'])) {
                        foreach ($this->getProducts($condition['in']) as $product) {
                            $productCollection->addItem($product);
                        }
                    }
                    return $productCollection;
                }
            );

        return $productCollection;
    }

    /**
     * Get link collection.
     *
     * @param array $ids
     * @return MockObject
     */
    private function _getLinkCollection(array $ids)
    {
        $linkCollection = $this->getMockBuilder(Collection::class)
            ->addMethods(['useCrossSellLinks', 'getProductCollection'])
            ->onlyMethods(
                [
                    'setStoreId',
                    'setPageSize',
                    'setGroupBy',
                    'setVisibility',
                    'addMinimalPrice',
                    'addFinalPrice',
                    'addTaxPercents',
                    'addAttributeToSelect',
                    'addUrlRewrite',
                    'getSelect',
                    'getIterator',
                    'addProductFilter'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $linkCollection->method('useCrossSellLinks')->willReturnSelf();
        $linkCollection->method('getProductCollection')->willReturnSelf();
        $linkCollection->method('setStoreId')->willReturnSelf();
        $linkCollection->method('setPageSize')->willReturnSelf();
        $linkCollection->method('setGroupBy')->willReturnSelf();
        $linkCollection->method('setVisibility')->willReturnSelf();
        $linkCollection->method('addMinimalPrice')->willReturnSelf();
        $linkCollection->method('addFinalPrice')->willReturnSelf();
        $linkCollection->method('addTaxPercents')->willReturnSelf();
        $linkCollection->method('addAttributeToSelect')->willReturnSelf();
        $linkCollection->method('addUrlRewrite')->willReturnSelf();
        $linkCollection->method('getSelect')->willReturn(
            $this->createMock(Select::class)
        );
        $linkCollection->method('getIterator')->willReturn($this->getProducts($ids));
        $linkCollection->method('addProductFilter')->willReturn([]);
        return $linkCollection;
    }

    /**
     * Generate products objects for provided entity IDs.
     *
     * @param array $ids
     * @return ArrayIterator
     */
    private function getProducts(array $ids): ArrayIterator
    {
        $items = [];
        foreach ($ids as $id) {
            $items[] = new DataObject(['entity_id' => $id]);
        }
        return new ArrayIterator($items);
    }

    /**
     * Generate array of random unique numbers
     *
     * @param int $start
     * @param int $end
     * @param int $limit
     * @return array
     */
    private function generateRandomIds(int $start, int $end, int $limit): array
    {
        $range = range($start, $end, 11);
        shuffle($range);
        return array_slice($range, 0, $limit);
    }
}
