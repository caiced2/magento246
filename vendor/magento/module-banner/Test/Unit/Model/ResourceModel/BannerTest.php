<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Banner\Test\Unit\Model\ResourceModel;

use Magento\Banner\Model\Config;
use Magento\Banner\Model\ResourceModel\Banner;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Select\SelectRenderer;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BannerTest extends TestCase
{
    /**
     * @var Banner
     */
    private $_resourceModel;

    /**
     * @var MockObject
     */
    private $_resource;

    /**
     * @var MockObject
     */
    private $_eventManager;

    /**
     * @var MockObject
     */
    private $_bannerConfig;

    /**
     * @var MockObject
     */
    private $connection;

    /**
     * @var SelectRenderer
     */
    protected $selectRenderer;

    protected function setUp(): void
    {
        $this->connection = $this->getMockForAbstractClass(
            Mysql::class,
            [],
            '',
            false,
            true,
            true,
            ['getTransactionLevel', 'fetchOne', 'select', 'prepareSqlCondition', '_connect', '_quote']
        );
        $this->selectRenderer = $this->getMockBuilder(SelectRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $select = new Select($this->connection, $this->selectRenderer);

        $this->connection->expects($this->once())->method('select')->willReturn($select);
        $this->connection->expects($this->any())->method('_quote')->willReturnArgument(0);

        $this->_resource = $this->createMock(ResourceConnection::class);
        $this->_resource->expects($this->any())->method('getTableName')->willReturnArgument(0);
        $this->_resource->expects(
            $this->any()
        )->method(
            'getConnection'
        )->willReturn(
            $this->connection
        );

        $this->_eventManager = $this->createPartialMock(ManagerInterface::class, ['dispatch']);

        $this->_bannerConfig = $this->createPartialMock(Config::class, ['explodeTypes']);

        $salesruleColFactory = $this->createPartialMock(
            \Magento\Banner\Model\ResourceModel\Salesrule\CollectionFactory::class,
            ['create']
        );

        $catRuleColFactory = $this->createPartialMock(
            \Magento\Banner\Model\ResourceModel\Catalogrule\CollectionFactory::class,
            ['create']
        );

        $contextMock = $this->createMock(Context::class);
        $contextMock->expects($this->once())->method('getResources')->willReturn($this->_resource);

        $this->_resourceModel = new Banner(
            $contextMock,
            $this->_eventManager,
            $this->_bannerConfig,
            $salesruleColFactory,
            $catRuleColFactory
        );
    }

    protected function tearDown(): void
    {
        $this->_resourceModel = null;
        $this->_resource = null;
        $this->_eventManager = null;
        $this->_bannerConfig = null;
        $this->connection = null;
    }

    public function testGetStoreContent()
    {
        $this->connection->expects(
            $this->once()
        )->method(
            'fetchOne'
        )->withAnyParameters(
        )->willReturn(
            'Dynamic Block Contents'
        );

        $this->_eventManager->expects(
            $this->once()
        )->method(
            'dispatch'
        )->with(
            'magento_banner_resource_banner_content_select_init',
            $this->arrayHasKey('select')
        );

        $this->assertEquals('Dynamic Block Contents', $this->_resourceModel->getStoreContent(123, 5));
    }

    public function testGetStoreContentFilterByTypes()
    {
        $bannerTypes = ['content', 'footer', 'header'];
        $this->_bannerConfig->expects(
            $this->once()
        )->method(
            'explodeTypes'
        )->with(
            $bannerTypes
        )->willReturn(
            ['footer', 'header']
        );
        $this->_resourceModel->filterByTypes($bannerTypes);

        $this->connection->expects(
            $this->exactly(2)
        )->method(
            'prepareSqlCondition'
        )->willReturnMap(
            [
                ['banner.types', ['finset' => 'footer'], 'banner.types IN ("footer")'],
                ['banner.types', ['finset' => 'header'], 'banner.types IN ("header")'],
            ]
        );
        $this->connection->expects(
            $this->once()
        )->method(
            'fetchOne'
        )->withAnyParameters(
        )->willReturn(
            'Dynamic Block Contents'
        );

        $this->_eventManager->expects(
            $this->once()
        )->method(
            'dispatch'
        )->with(
            'magento_banner_resource_banner_content_select_init',
            $this->arrayHasKey('select')
        );

        $this->assertEquals('Dynamic Block Contents', $this->_resourceModel->getStoreContent(123, 5));
    }
}
