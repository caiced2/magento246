<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Test\Unit\Controller\Adminhtml\Cms\Hierarchy;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Session;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Message\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\VersionsCms\Controller\Adminhtml\Cms\Hierarchy\Save;
use Magento\VersionsCms\Model\Hierarchy\Node;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $request;

    /**
     * @var MockObject
     */
    protected $jsonHelper;

    /**
     * @var MockObject
     */
    protected $node;

    /**
     * @var MockObject
     */
    protected $objectManagerMock;

    /**
     * @var MockObject
     */
    protected $response;

    /**
     * @var MockObject
     */
    protected $session;

    /**
     * @var MockObject
     */
    protected $actionFlag;

    /**
     * @var MockObject
     */
    protected $context;

    /**
     * @var MockObject
     */
    protected $backendHelper;

    /**
     * @var MockObject
     */
    protected $messageManager;

    /**
     * @var Save
     */
    protected $saveController;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Http::class);
        $this->jsonHelper = $this->createMock(Data::class);
        $this->node = $this->createMock(Node::class);
        $this->node->expects($this->once())->method('collectTree');
        $this->objectManagerMock = $this->getMockForAbstractClass(ObjectManagerInterface::class);
        $this->response = $this->getMockBuilder(ResponseInterface::class)
            ->addMethods(['setRedirect'])
            ->onlyMethods(['sendResponse'])
            ->getMockForAbstractClass();
        $this->messageManager = $this->createMock(Manager::class);
        $this->session = $this->getMockBuilder(Session::class)
            ->addMethods(['setIsUrlNotice'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->actionFlag = $this->createPartialMock(ActionFlag::class, ['get']);
        $this->backendHelper = $this->createPartialMock(\Magento\Backend\Helper\Data::class, ['getUrl']);
        $this->context = $this->createMock(Context::class);
        $objectManager = new ObjectManager($this);
        $this->saveController = $objectManager->getObject(
            Save::class,
            [
                'request' => $this->request,
                'response' => $this->response,
                'helper' => $this->backendHelper,
                'objectManager' => $this->objectManagerMock,
                'session' => $this->session,
                'actionFlag' => $this->actionFlag,
                'messageManager' => $this->messageManager
            ]
        );
    }

    /**
     * @param int $nodesDataEncoded
     * @param array $nodesData
     * @param array $post
     * @param string $path
     */
    protected function prepareTests($nodesDataEncoded, $nodesData, $post, $path)
    {
        $this->request->expects($this->atLeastOnce())->method('isPost')->willReturn(true);
        $this->request->expects($this->atLeastOnce())->method('getPostValue')->willReturn($post);

        $this->jsonHelper->expects($this->once())
            ->method('jsonDecode')
            ->with($nodesDataEncoded)
            ->willReturn($nodesData);

        $this->node->expects($this->once())->method('collectTree')->with($nodesData, []);

        $this->objectManagerMock->expects($this->atLeastOnce())
            ->method('create')
            ->with(Node::class)
            ->willReturn($this->node);
        $this->objectManagerMock->expects($this->once())
            ->method('get')
            ->with(Data::class)
            ->willReturn($this->jsonHelper);

        $this->response->expects($this->once())->method('setRedirect')->with($path);

        $this->session->expects($this->once())->method('setIsUrlNotice')->with(true);

        $this->actionFlag->expects($this->once())->method('get')->with('', 'check_url_settings')->willReturn(true);

        $this->backendHelper->expects($this->atLeastOnce())->method('getUrl')->with($path)->willReturn($path);
    }

    /**
     * @param int $nodesDataEncoded
     * @param array $nodesData
     * @param array $post
     * @param string $path
     *
     * @dataProvider successMessageDisplayedDataProvider
     */
    public function testSuccessMessageDisplayed($nodesDataEncoded, $nodesData, $post, $path)
    {
        $this->prepareTests($nodesDataEncoded, $nodesData, $post, $path);

        $this->messageManager->expects($this->once())->method('addSuccess')->with(__('You have saved the hierarchy.'));

        $this->saveController->execute();
    }

    /**
     * @param int $nodesDataEncoded
     * @param array $nodesData
     * @param array $post
     * @param string $path
     *
     * @dataProvider successMessageNotDisplayedDataProvider
     */
    public function testSuccessMessageNotDisplayed($nodesDataEncoded, $nodesData, $post, $path)
    {
        $this->prepareTests($nodesDataEncoded, $nodesData, $post, $path);

        $this->messageManager->expects($this->never())->method('addSuccess');

        $this->saveController->execute();
    }

    /**
     * @return array
     */
    public function successMessageDisplayedDataProvider()
    {
        return [
            [
                'nodesDataEncoded' => 1,
                'nodesData' => [
                    [
                        'node_id' => 0,
                        'label' => 'Trial node',
                        'identifier' => 'trial',
                        'meta_chapter' => 0,
                        'meta_section' => 0,
                    ],
                    [
                        'node_id' => 1,
                        'label' => 'Trial node 1',
                        'identifier' => 'trial1',
                        'meta_chapter' => 0,
                        'meta_section' => 0,
                    ]
                ],
                'post' => [
                    'nodes_data' => 1,
                ],
                'path' => 'adminhtml/*/index',
            ]
        ];
    }

    /**
     * @return array
     */
    public function successMessageNotDisplayedDataProvider()
    {
        return [
            [
                'nodesDataEncoded' => 1,
                'nodesData' => [],
                'post' => [
                    'nodes_data' => 1,
                ],
                'path' => 'adminhtml/*/index',
            ]
        ];
    }
}
