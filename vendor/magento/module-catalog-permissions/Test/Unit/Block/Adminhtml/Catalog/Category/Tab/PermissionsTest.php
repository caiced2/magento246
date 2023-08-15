<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Block\Adminhtml\Catalog\Category\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\Tree;
use Magento\CatalogPermissions\Block\Adminhtml\Catalog\Category\Tab\Permissions;
use Magento\CatalogPermissions\Helper\Data;
use Magento\CatalogPermissions\Model\Permission\IndexFactory;
use Magento\CatalogPermissions\Model\ResourceModel\Permission\CollectionFactory;
use Magento\Customer\Model\ResourceModel\Group\Collection;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PermissionsTest extends TestCase
{
    /**
     * @var Permissions
     */
    protected $model;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManagerMock;

    /**
     * @var RequestInterface
     */
    protected $requestMock;

    /**
     * @var Tree
     */
    protected $categoryTree;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * @var IndexFactory
     */
    protected $permIndexFactory;

    /**
     * @var CollectionFactory
     */
    protected $permissionCollectionFactory;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Group\CollectionFactory
     */
    protected $groupCollectionFactory;

    /**
     * @var Data
     */
    protected $catalogPermData;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);

        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);

        $this->context = $this->createPartialMock(
            Context::class,
            ['getStoreManager', 'getRequest']
        );

        $this->context->expects($this->any())->method('getStoreManager')->willReturn(
            $this->storeManagerMock
        );

        $this->context->expects($this->any())->method('getRequest')->willReturn(
            $this->requestMock
        );

        $this->categoryTree = $this->createMock(Tree::class);

        $this->registry = $this->createPartialMock(Registry::class, ['registry']);

        $this->categoryFactory = $this->createPartialMock(CategoryFactory::class, ['create']);

        $this->jsonEncoder = $this->getMockForAbstractClass(EncoderInterface::class);
        $this->jsonEncoder->method('encode')
            ->willReturnCallback('json_encode');

        $this->permIndexFactory = $this->getMockBuilder(IndexFactory::class)
            ->disableOriginalConstructor()
            ->addMethods(['getIndexForCategory'])
            ->onlyMethods(['create'])
            ->getMock();

        $this->permissionCollectionFactory = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );

        $this->groupCollectionFactory = $this->getMockBuilder(
            \Magento\Customer\Model\ResourceModel\Group\CollectionFactory::class
        )->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->catalogPermData = $this->createMock(Data::class);

        $this->model = new Permissions(
            $this->context,
            $this->categoryTree,
            $this->registry,
            $this->categoryFactory,
            $this->jsonEncoder,
            $this->permIndexFactory,
            $this->permissionCollectionFactory,
            $this->groupCollectionFactory,
            $this->catalogPermData
        );
    }

    /**
     * @param int $categoryId
     * @param array $index
     * @param array $groupIds
     * @param array $result
     * @dataProvider getParentPermissionsDataProvider
     */
    public function testGetParentPermissions($categoryId, $index, $groupIds, $result)
    {
        $categoryMock = $this->createPartialMock(Category::class, ['getId', 'getParentId']);

        $websiteMock = $this->createPartialMock(Website::class, ['getId', 'getDefaultStore']);

        $categoryMock->expects($this->any())->method('getId')->willReturn($categoryId);
        $categoryMock->expects($this->any())->method('getParentId')->willReturn(1);
        $websiteMock->expects($this->any())->method('getId')->willReturn(1);
        $websiteMock->expects($this->any())->method('getDefaultStore')->willReturn(1);

        $this->registry->expects($this->any())->method('registry')->willReturn($categoryMock);
        $this->permIndexFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->permIndexFactory->expects($this->any())->method('getIndexForCategory')->willReturn($index);
        $this->catalogPermData->expects($this->any())->method('isAllowedCategoryView')->willReturn(true);
        $this->catalogPermData->expects($this->any())->method('isAllowedProductPrice')->willReturn(true);
        $this->catalogPermData->expects($this->any())->method('isAllowedCheckoutItems')->willReturn(true);
        $groupCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $groupCollection->expects($this->any())->method('getAllIds')->willReturn($groupIds);
        $this->groupCollectionFactory->expects($this->any())->method('create')->willReturn($groupCollection);
        $this->requestMock->expects($this->any())->method('getParam')->willReturn(1);
        $this->storeManagerMock->expects($this->any())->method('getWebsites')->willReturn(
            [$websiteMock]
        );
        $this->assertEquals($result, $this->model->getParentPermissions());
    }

    /**
     * @return array
     */
    public function getParentPermissionsDataProvider()
    {
        $index = [
            1 => [
                'website_id' => 1,
                'customer_group_id' => 1,
                'grant_catalog_category_view' => '0',
                'grant_catalog_product_price' => '-1',
                'grant_checkout_items' => '-2',
            ],
            2 => [
                'website_id' => 2,
                'customer_group_id' => 2,
                'grant_catalog_category_view' => '-1',
                'grant_catalog_product_price' => '-2',
                'grant_checkout_items' => '0',
            ],
        ];
        $groupIds = [1, 2];
        $groupIdsSecond = [1, 2, 3];
        $result = [
            '1_1' => ['category' => '-1', 'product' => '-1', 'checkout' => '-2'],
            '2_2' => ['category' => '-1', 'product' => '-2', 'checkout' => '0'],
            '1_2' => ['category' => '-1', 'product' => '-1', 'checkout' => '-1'],
        ];
        $resultSecond = [
            '1_1' => ['category' => '-1', 'product' => '-1', 'checkout' => '-2'],
            '2_2' => ['category' => '-1', 'product' => '-2', 'checkout' => '0'],
            '1_2' => ['category' => '-1', 'product' => '-1', 'checkout' => '-1'],
            '1_3' => ['category' => '-1', 'product' => '-1', 'checkout' => '-1'],
        ];
        return [[3, $index, $groupIds, $result], [0, $index, $groupIdsSecond, $resultSecond]];
    }

    /**
     * Test getConfigJson() method
     */
    public function testGetConfigJson()
    {
        $websiteId = 101;
        $websites = [];
        $website = $this->createMock(WebsiteInterface::class);
        $website->method('getId')
            ->willReturn($websiteId);
        $websites[] = $website;
        $category = $this->createMock(Category::class);
        $this->registry->method('registry')
            ->willReturn($category);
        $this->storeManagerMock->method('getWebsites')
            ->willReturn($websites);
        $groupCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $groupCollection->expects($this->any())->method('getAllIds')->willReturn([]);
        $this->groupCollectionFactory->method('create')
            ->willReturn($groupCollection);
        $layout = $this->createMock(LayoutInterface::class);
        $this->model->setLayout($layout);
        $config = json_decode($this->model->getConfigJson(), true);
        $this->assertEquals($websiteId, $config['website_id']);
    }
}
