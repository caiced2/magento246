<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Test\Unit\Block\Widget;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Escaper;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\VersionsCms\Block\Widget\Node;
use Magento\VersionsCms\Model\CurrentNodeResolverInterface;
use Magento\VersionsCms\Model\Hierarchy\NodeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class NodeTest extends TestCase
{
    /**
     * @var Node
     */
    protected $nodeWidget;

    /**
     * @var Store|MockObject
     */
    protected $storeMock;

    /**
     * @var \Magento\VersionsCms\Model\Hierarchy\Node|MockObject
     */
    protected $nodeMock;

    /**
     * @var NodeFactory|MockObject
     */
    protected $hierarchyNodeFactoryMock;

    /**
     * @var string
     */
    protected $nodeLabel = 'Node Label';

    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var CurrentNodeResolverInterface|MockObject
     */
    private $currentNodeResolverMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var ManagerInterface|MockObject
     */
    private $eventManagerMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var Escaper|MockObject
     */
    private $escaperMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->storeMock = $this->getMockBuilder(Store::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->nodeMock = $this->getMockBuilder(\Magento\VersionsCms\Model\Hierarchy\Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->hierarchyNodeFactoryMock = $this->getMockBuilder(NodeFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->getMockForAbstractClass();

        $this->eventManagerMock = $this->getMockBuilder(ManagerInterface::class)
            ->getMockForAbstractClass();

        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->getMockForAbstractClass();

        $this->escaperMock = $this->getMockBuilder(Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->contextMock->expects($this->any())
            ->method('getStoreManager')
            ->willReturn($this->storeManagerMock);
        $this->contextMock->expects($this->any())
            ->method('getEventManager')
            ->willReturn($this->eventManagerMock);
        $this->contextMock->expects($this->any())
            ->method('getEscaper')
            ->willReturn($this->escaperMock);
        $this->contextMock->expects($this->any())
            ->method('getScopeConfig')
            ->willReturn($this->scopeConfigMock);

        $this->currentNodeResolverMock = $this->getMockBuilder(CurrentNodeResolverInterface::class)
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->nodeWidget = $objectManagerHelper->getObject(
            Node::class,
            [
                'hierarchyNodeFactory' => $this->hierarchyNodeFactoryMock,
                'currentNodeResolver' => $this->currentNodeResolverMock,
                'context' => $this->contextMock,
            ]
        );
    }

    /**
     * @param int $storeId
     * @param array $data
     * @param string $value
     * @return void
     *
     * @dataProvider getLabelDataProvider
     */
    public function testGetLabel($storeId, $data, $value)
    {
        $this->emulateToHtmlMethod();
        $this->storeMock->expects($this->any())
            ->method('getId')
            ->willReturn($storeId);

        $this->nodeWidget->setData($data);
        $this->assertEquals($value, $this->nodeWidget->getLabel());
    }

    /**
     * @return array
     */
    public function getLabelDataProvider()
    {
        return [
            [
                'storeId' => 1,
                'data' => ['anchor_text_1' => 'value_1'],
                'value' => 'value_1',
            ],
            [
                'storeId' => 1,
                'data' => ['anchor_text_1' => ''],
                'value' => $this->nodeLabel,
            ],
            [
                'storeId' => 1,
                'data' => ['anchor_text_1' => 'value_1', 'anchor_text_0' => 'value_0'],
                'value' => 'value_1',
            ],
            [
                'storeId' => 0,
                'data' => ['anchor_text_1' => 'value_1', 'anchor_text_0' => 'value_0'],
                'value' => 'value_0',
            ],
            [
                'storeId' => 1,
                'data' => ['anchor_text_1' => 'value_1', 'anchor_text_0' => 'value_0', 'anchor_text' => 'value'],
                'value' => 'value_1',
            ],
            [
                'storeId' => 0,
                'data' => ['anchor_text_1' => 'value_1', 'anchor_text_0' => 'value_0', 'anchor_text' => 'value'],
                'value' => 'value_0',
            ],
            [
                'storeId' => 1,
                'data' => ['anchor_text_0' => 'value_0', 'anchor_text' => 'value'],
                'value' => 'value_0',
            ],
            [
                'storeId' => 1,
                'data' => ['anchor_text_2' => 'value_2', 'anchor_text' => 'value'],
                'value' => 'value',
            ],
            [
                'storeId' => 1,
                'data' => ['anchor_text' => null, 'anchor_text_1' => null],
                'value' => $this->nodeLabel,
            ],
        ];
    }

    /**
     * @param int $storeId
     * @param array $data
     * @param string $value
     * @return void
     *
     * @dataProvider getTitleDataProvider
     */
    public function testGetTitle($storeId, $data, $value)
    {
        $nodeId = 1;
        $this->nodeWidget->setData(['node_id' => $nodeId]);
        $this->hierarchyNodeFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->nodeMock);
        $this->nodeMock->expects($this->once())
            ->method('load')
            ->with($nodeId)
            ->willReturnSelf();
        $this->nodeMock->expects($this->any())
            ->method('getLabel')
            ->willReturn($this->nodeLabel);
        $this->nodeWidget->toHtml();

        $this->storeMock->expects($this->any())
            ->method('getId')
            ->willReturn($storeId);

        $this->nodeWidget->setData($data);
        $this->assertEquals($value, $this->nodeWidget->getTitle());
    }

    /**
     * @return array
     */
    public function getTitleDataProvider()
    {
        return [
            [
                'storeId' => 1,
                'data' => ['title_1' => 'value_1'],
                'value' => 'value_1',
            ],
            [
                'storeId' => 1,
                'data' => ['title_1' => ''],
                'value' => $this->nodeLabel,
            ],
            [
                'storeId' => 1,
                'data' => ['title_1' => false],
                'value' => $this->nodeLabel,
            ],
            [
                'storeId' => 1,
                'data' => ['title_1' => 'value_1', 'title_0' => 'value_0'],
                'value' => 'value_1',
            ],
            [
                'storeId' => 0,
                'data' => ['title_1' => 'value_1', 'title_0' => 'value_0'],
                'value' => 'value_0',
            ],
            [
                'storeId' => 1,
                'data' => ['title_1' => 'value_1', 'title_0' => 'value_0', 'title' => 'value'],
                'value' => 'value_1',
            ],
            [
                'storeId' => 0,
                'data' => ['title_1' => 'value_1', 'title_0' => 'value_0', 'title' => 'value'],
                'value' => 'value_0',
            ],
            [
                'storeId' => 1,
                'data' => ['title_0' => 'value_0', 'title' => 'value'],
                'value' => 'value_0',
            ],
            [
                'storeId' => 1,
                'data' => ['title_2' => 'value_2', 'title' => 'value'],
                'value' => 'value',
            ],
            [
                'storeId' => 1,
                'data' => ['title' => null, 'title_1' => null],
                'value' => $this->nodeLabel,
            ],
        ];
    }

    /**
     * @return void
     */
    public function testGetHref()
    {
        $url = 'http://localhost/';
        $this->emulateToHtmlMethod();
        $this->nodeMock->expects($this->once())
            ->method('getUrl')
            ->willReturn($url);
        $this->assertSame($url, $this->nodeWidget->getHref());
    }

    /**
     * @param int $storeId
     * @param array $data
     * @param string $value
     * @return void
     *
     * @dataProvider getNodeIdDataProvider
     */
    public function testGetNodeId($storeId, $data, $value)
    {
        $this->storeMock->expects($this->any())
            ->method('getId')
            ->willReturn($storeId);

        $this->nodeWidget->setData($data);
        $this->assertEquals($value, $this->nodeWidget->getNodeId());
    }

    /**
     * @return array
     */
    public function getNodeIdDataProvider()
    {
        return [
            [
                $storeId = 1,
                $data = ['node_id_1' => 'value_1'],
                $value = 'value_1'
            ],
            [
                $storeId = 1,
                $data = ['node_id_1' => 'value_1', 'node_id_0' => 'value_0'],
                $value = 'value_1'
            ],
            [
                $storeId = 1,
                $data = ['node_id_1' => 'value_1', 'node_id_0' => 'value_0', 'node_id' => 'value'],
                $value = 'value_1'
            ],
            [
                $storeId = 1,
                $data = ['node_id_0' => 'value_0', 'node_id' => 'value'],
                $value = 'value_0'
            ],
            [
                $storeId = 1,
                $data = ['node_id_2' => 'value_2', 'node_id' => 'value'],
                $value = 'value'
            ],
            [
                'storeId' => 1,
                'data' => ['node_id' => null, 'node_id_1' => null],
                'value' => false
            ]
        ];
    }

    /**
     * Emulate execution of current block's toHtml() method
     *
     * Helper method, that emulates execution of toHtml() method of \Magento\VersionsCms\Block\Widget\Node object.
     * Required for testGetHref and testGetLabel test iterations.
     *
     * @return void
     */
    protected function emulateToHtmlMethod()
    {
        $this->currentNodeResolverMock->expects($this->once())
            ->method('get')
            ->with($this->requestMock)
            ->willReturn($this->nodeMock);

        $this->nodeMock->expects($this->any())
            ->method('getLabel')
            ->willReturn($this->nodeLabel);

        /** @var DataObject */
        $transportObject = new DataObject(
            [
                'html' => '<li><a title="" ></a></li>',
            ]
        );

        $this->eventManagerMock->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnMap([
                [
                    'view_block_abstract_to_html_before',
                    [
                        'block' => $this->nodeWidget,
                    ],
                    $this->eventManagerMock
                ],
                [
                    'view_block_abstract_to_html_after',
                    [
                        'block' => $this->nodeWidget,
                        'transport' => $transportObject,
                    ],
                    $this->eventManagerMock
                ],
            ]);

        $this->nodeWidget->toHtml();
    }
}
