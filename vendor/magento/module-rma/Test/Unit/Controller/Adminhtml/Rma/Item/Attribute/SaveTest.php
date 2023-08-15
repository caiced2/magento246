<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Controller\Adminhtml\Rma\Item\Attribute;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Helper\Data;
use Magento\Backend\Model\Session;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\View;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Message\Manager;
use Magento\Framework\Serialize\Serializer\FormData;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Rma\Controller\Adminhtml\Rma\Item\Attribute\Save;
use Magento\Rma\Model\Item\Attribute;
use Magento\Store\Model\WebsiteFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class SaveTest extends TestCase
{
    /**
     * @var Save
     */
    protected $action;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var Http|MockObject
     */
    protected $requestMock;

    /**
     * @var \Magento\Framework\App\Response\Http|MockObject
     */
    protected $responseMock;

    /**
     * @var \Magento\Framework\ObjectManager\ObjectManager|MockObject
     */
    protected $objectManagerMock;

    /**
     * @var Attribute|MockObject
     */
    protected $attributeMock;

    /**
     * @var Type|MockObject
     */
    protected $entityTypeMock;

    /**
     * @var Session|MockObject
     */
    protected $sessionMock;

    /**
     * @var View|MockObject
     */
    protected $viewMock;

    /**
     * @var BlockInterface|MockObject
     */
    protected $blockMock;

    /**
     * @var Data|MockObject
     */
    protected $helperMock;

    /**
     * @var \Magento\CustomAttributeManagement\Helper\Data|MockObject
     */
    protected $attributeHelperMock;

    /**
     * @var ActionFlag|MockObject
     */
    protected $flagMock;

    /**
     * @var Config|MockObject
     */
    protected $eavConfigMock;

    /**
     * @var \Magento\Eav\Model\Entity\Attribute\Set|MockObject
     */
    protected $attributeSetMock;

    /**
     * @var Manager|MockObject
     */
    protected $messageManagerMock;

    /**
     * @var MockObject
     */
    protected $websiteFactoryMock;

    /**
     * @var FormData|MockObject
     */
    private $formDataSerializerMock;

    /**
     * Set up before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->websiteFactoryMock = $this->createPartialMock(WebsiteFactory::class, ['create']);
        $this->contextMock = $this->createMock(Context::class);
        $this->requestMock = $this->createMock(Http::class);
        $this->responseMock = $this->createMock(\Magento\Framework\App\Response\Http::class);
        $this->attributeMock = $this->createMock(Attribute::class);
        $this->attributeSetMock = $this->createMock(Set::class);
        $this->entityTypeMock = $this->createMock(Type::class);
        $this->objectManagerMock = $this->createMock(\Magento\Framework\ObjectManager\ObjectManager::class);
        $this->sessionMock = $this->createMock(Session::class);
        $this->viewMock = $this->createMock(View::class);
        $this->helperMock = $this->createMock(Data::class);
        $this->attributeHelperMock = $this->createMock(\Magento\CustomAttributeManagement\Helper\Data::class);
        $this->flagMock = $this->createMock(ActionFlag::class);
        $this->eavConfigMock = $this->createMock(Config::class);
        $this->messageManagerMock = $this->createMock(Manager::class);
        $this->blockMock = $this->getMockForAbstractClass(
            BlockInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['setActive', 'getMenuModel', 'getParentItems', 'addLink', 'getConfig', 'getTitle', 'prepend']
        );
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getHelper')
            ->willReturn($this->helperMock);
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getResponse')
            ->willReturn($this->responseMock);
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getSession')
            ->willReturn($this->sessionMock);
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getObjectManager')
            ->willReturn($this->objectManagerMock);
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getView')
            ->willReturn($this->viewMock);
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getActionFlag')
            ->willReturn($this->flagMock);
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getMessageManager')
            ->willReturn($this->messageManagerMock);
        $this->formDataSerializerMock = $this->getMockBuilder(FormData::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->action = $this->objectManager->getObject(
            Save::class,
            [
                'context' => $this->contextMock,
                'websiteFactory' => $this->websiteFactoryMock,
                'formDataSerializer' => $this->formDataSerializerMock,
            ]
        );
    }

    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $this->requestMock->expects($this->once())
            ->method('getPostValue')
            ->willReturn([
                'frontend_input'=> '',
            ]);
        $this->requestMock
            ->method('getParam')
            ->willReturnMap([
                ['serialized_options', '[]', ''],
            ]);
        $this->formDataSerializerMock
            ->expects($this->once())
            ->method('unserialize')
            ->with('')
            ->willReturn([]);
        $this->attributeHelperMock->expects($this->once())
            ->method('filterPostData')
            ->willReturn(['frontend_input' => 'frontend_input']);
        $this->attributeHelperMock->expects($this->once())
            ->method('checkValidateRules')
            ->willReturn([]);
        $this->attributeHelperMock->expects($this->once())
            ->method('getAttributeBackendModelByInputType')
            ->willReturn('AttributeBackendModelByInputType');
        $this->attributeHelperMock->expects($this->once())
            ->method('getAttributeSourceModelByInputType')
            ->willReturn('AttributeSourceModelByInputType');
        $this->attributeHelperMock->expects($this->once())
            ->method('getAttributeBackendTypeByInputType')
            ->willReturn('AttributeBackendTypeByInputType');

        $this->eavConfigMock->expects($this->once())
            ->method('getEntityType')
            ->with('rma_item')
            ->willReturn($this->entityTypeMock);
        $this->entityTypeMock->expects($this->once())
            ->method('getDefaultAttributeSetId')
            ->willReturn(1);
        $this->requestMock->expects($this->once())
            ->method('isPost')
            ->willReturn(true);
        $this->objectManagerMock->expects($this->any())
            ->method('create')
            ->willReturnMap(
                [
                    [Attribute::class, [], $this->attributeMock],
                    [Set::class, [], $this->attributeSetMock],
                ]
            );

        $this->objectManagerMock->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    [\Magento\CustomAttributeManagement\Helper\Data::class, $this->attributeHelperMock],
                    [Config::class, $this->eavConfigMock],
                ]
            );
        $this->messageManagerMock->expects($this->once())->method('addSuccess');
        $this->assertEmpty($this->action->execute());
    }

    /**
     * @throws NotFoundException
     */
    public function testExecuteWithOptionsDataError()
    {
        $serializedOptions = '{"key":"value"}';
        $message = "The attribute couldn't be saved due to an error. Verify your information and try again. "
            . "If the error persists, please try again later.";

        $this->requestMock
            ->expects($this->any())
            ->method('getParam')
            ->willReturnMap([
                ['serialized_options', '[]', $serializedOptions],
            ]);
        $this->messageManagerMock
            ->expects($this->once())
            ->method('addErrorMessage')
            ->with($message);

        $this->formDataSerializerMock
            ->expects($this->once())
            ->method('unserialize')
            ->with($serializedOptions)
            ->willThrowException(new \InvalidArgumentException('Some exception'));

        $this->assertEmpty($this->action->execute());
    }
}
