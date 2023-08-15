<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Test\Unit\Model\Plugin;

use Magento\AdminGws\Block\Adminhtml\Permissions\Tab\Rolesedit\Gws;
use Magento\Backend\Model\Session;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\User\Controller\Adminhtml\User\Role\SaveRole as SaveRoleController;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for \Magento\AdminGws\Model\Plugin\SaveRole testing
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveRoleTest extends TestCase
{
    /**
     * @var \Magento\AdminGws\Model\Plugin\SaveRole
     */
    private $model;

    /**
     * @var RedirectInterface|MockObject
     */
    private $resultRedirectMock;

    /**
     * Request object
     *
     * @var RequestInterface
     */
    private $requestMock;

    /**
     * Request object
     *
     * @var Session
     */
    private $backendSessionMock;

    /**
     * @var DataObject
     */
    private $session;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->resultRedirectMock = $this->getMockForAbstractClass(
            RedirectInterface::class,
            [],
            '',
            false
        );
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPostValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->session = new DataObject();

        $this->backendSessionMock = $this->getMockBuilder(Session::class)
            ->addMethods(['setData'])
            ->onlyMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->backendSessionMock->method('setData')
            ->willReturnCallback([$this->session, 'setData']);

        $this->backendSessionMock->method('getData')
            ->willReturnCallback(
                function ($key) {
                    return $this->session->getData($key);
                }
            );

        $this->model = $objectManager->getObject(
            \Magento\AdminGws\Model\Plugin\SaveRole::class,
            [
                'resultRedirect' => $this->resultRedirectMock,
                'request' => $this->requestMock,
                'backendSession' => $this->backendSessionMock,
            ]
        );
    }

    /**
     * Checks a test case when to save post data in the session.
     *
     * @param array $postData
     * @param array $sessionDataBefore
     * @param array $sessionDataAfter
     * @dataProvider afterExecuteDataProvider
     */
    public function testAfterExecute(array $postData, array $sessionDataBefore, array $sessionDataAfter)
    {
        $this->session->setData($sessionDataBefore);
        $saveRoleController = $this->createMock(SaveRoleController::class);
        $result = $this->createMock(Redirect::class);
        $this->requestMock->expects($this->atMost(1))
            ->method('getPostValue')
            ->willReturn($postData);
        $this->assertEquals($result, $this->model->afterExecute($saveRoleController, $result));
        $this->assertEquals($sessionDataAfter, $this->session->getData());
    }

    /**
     * @return array
     */
    public function afterExecuteDataProvider()
    {
        return [
            'should save post data in the session when validation failed' => [
                'post_data' => [
                    'gws_is_all' => 'gws_is_all_value',
                    'gws_websites' => 'gws_websites_value',
                    'gws_store_groups' => 'gws_store_groups_value',
                ],
                'session_data_before' => [
                    SaveRoleController::ROLE_EDIT_FORM_DATA_SESSION_KEY => 1
                ],
                'session_data_after' => [
                    Gws::SCOPE_ALL_FORM_DATA_SESSION_KEY => 'gws_is_all_value',
                    Gws::SCOPE_WEBSITE_FORM_DATA_SESSION_KEY => 'gws_websites_value',
                    Gws::SCOPE_STORE_FORM_DATA_SESSION_KEY => 'gws_store_groups_value',
                    SaveRoleController::ROLE_EDIT_FORM_DATA_SESSION_KEY => 1
                ]
            ],
            'should not save post data in the session when validation passed' => [
                'post_data' => [
                    'gws_is_all' => 'gws_is_all_value',
                    'gws_websites' => 'gws_websites_value',
                    'gws_store_groups' => 'gws_store_groups_value',
                ],
                'session_data_before' => [
                    'some_key' => 'some_value'
                ],
                'session_data_after' => [
                    'some_key' => 'some_value'
                ]
            ]
        ];
    }
}
