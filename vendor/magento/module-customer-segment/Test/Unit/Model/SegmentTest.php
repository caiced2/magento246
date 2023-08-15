<?php declare(strict_types=1);
/**
 * Unit test for CustomerSegment \Magento\CustomerSegment\Model\Segment
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Test class for \Magento\CustomerSegment\Model\Segment testing
 */
namespace Magento\CustomerSegment\Test\Unit\Model;

use Magento\CustomerSegment\Model\Segment;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\QueryResolver;
use Magento\Rule\Model\Condition\Combine;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SegmentTest extends TestCase
{
    /**
     * @var Segment
     */
    protected $model;

    /**
     * @var StoreManager|MockObject
     */
    protected $storeManagerMock;

    /**
     * @var QueryResolver|MockObject
     */
    protected $queryResolverMock;

    /**
     * @var \Magento\CustomerSegment\Model\ResourceModel\Segment|MockObject
     */
    protected $resourceMock;

    protected function setUp(): void
    {
        $this->storeManagerMock = $this->createMock(StoreManager::class);
        $this->queryResolverMock = $this->createMock(QueryResolver::class);
        $this->resourceMock = $this->createMock(\Magento\CustomerSegment\Model\ResourceModel\Segment::class);

        $helper = new ObjectManager($this);
        $this->prepareObjectManager([
            [
                ExtensionAttributesFactory::class,
                $this->createMock(ExtensionAttributesFactory::class)
            ],
            [
                AttributeValueFactory::class,
                $this->createMock(AttributeValueFactory::class)
            ],
        ]);
        $this->model = $helper->getObject(
            Segment::class,
            [
                'storeManager' => $this->storeManagerMock,
                'queryResolver' => $this->queryResolverMock,
                'resource' => $this->resourceMock
            ]
        );
    }

    protected function tearDown(): void
    {
        $this->model = null;
        $this->storeManagerMock = null;
        $this->queryResolverMock = null;
        $this->resourceMock = null;
    }

    /**
     * @param array $websiteData
     * @param null|int $withParam
     */
    protected function prepareWebsite($websiteData = [], $withParam = null)
    {
        $website = new DataObject($websiteData);
        $this->storeManagerMock
            ->expects($this->once())
            ->method('getWebsite')
            ->with($withParam)
            ->willReturn($website);
    }

    public function testValidateCustomerWithEmptyQuery()
    {
        $this->prepareWebsite();
        $this->assertFalse($this->model->validateCustomer(null, null));
    }

    public function testValidateCustomerForVisitor()
    {
        $this->prepareWebsite(['id' => 1], 1);

        $sql = 'select :quote_id :visitor_id';
        $this->model->setData('condition_sql', $sql);
        $this->model->setVisitorId('visitor_1');
        $this->model->setQuoteId('quote_1');

        $conditions = $this->getMockBuilder(Combine::class)
            ->disableOriginalConstructor()
            ->setMethods(['isSatisfiedBy'])
            ->getMock();
        $params = [
            'quote_id' => 'quote_1',
            'visitor_id' => 'visitor_1',
        ];
        $conditions->expects($this->once())
            ->method('isSatisfiedBy')
            ->with(null, 1, $params)
            ->willReturn(true);
        $this->model->setConditions($conditions);
        $this->assertTrue($this->model->validateCustomer(null, 1));
    }

    /**
     * @param $map
     */
    private function prepareObjectManager($map)
    {
        $objectManagerMock = $this->getMockBuilder(ObjectManagerInterface::class)
            ->setMethods(['getInstance'])
            ->getMockForAbstractClass();
        $objectManagerMock->expects($this->any())->method('getInstance')->willReturnSelf();
        $objectManagerMock->expects($this->any())
            ->method('get')
            ->willReturnMap($map);
        $reflectionClass = new \ReflectionClass(\Magento\Framework\App\ObjectManager::class);
        $reflectionProperty = $reflectionClass->getProperty('_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($objectManagerMock);
    }
}
