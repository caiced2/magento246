<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomAttributeManagement\Test\Unit\Block\Adminhtml\Renderer\Fieldset;

use Magento\Backend\Block\Template\Context;
use Magento\CustomAttributeManagement\Block\Adminhtml\Form\Renderer\Fieldset\Element;
use Magento\Customer\Model\Attribute;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ElementTest extends TestCase
{
    /** @var  Element */
    protected $model;

    /** @var  Context|MockObject */
    protected $context;

    /** @var  AbstractElement|MockObject */
    protected $element;

    /** @var  ManagerInterface|MockObject */
    protected $eventManager;

    /** @var  ScopeConfigInterface|MockObject */
    protected $scopeConfig;

    /** @var  Form|MockObject */
    protected $form;

    /** @var  Attribute|MockObject */
    protected $attribute;

    /** @var  Website|MockObject */
    protected $website;

    /** @var  RequestInterface|MockObject */
    protected $request;

    protected function setUp(): void
    {
        $this->prepareContext();

        $this->element = $this->getMockBuilder(AbstractElement::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getScope',
            ])
            ->getMock();

        $this->form = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getDataObject',
            ])
            ->getMock();

        $this->attribute = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->website = $this->getMockBuilder(Website::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new Element(
            $this->context
        );
    }

    protected function prepareContext()
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventManager = $this->getMockBuilder(ManagerInterface::class)
            ->setMethods([
                'dispatch',
            ])
            ->getMockForAbstractClass();

        $this->context->expects($this->any())
            ->method('getEventManager')
            ->willReturn($this->eventManager);

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->setMethods([
                'getValue',
            ])
            ->getMockForAbstractClass();

        $this->context->expects($this->any())
            ->method('getScopeConfig')
            ->willReturn($this->scopeConfig);

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->getMockForAbstractClass();

        $this->context->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->request);
    }

    public function testCanDisplayUseDefaultNoElement()
    {
        $this->assertFalse($this->model->canDisplayUseDefault());
    }

    /**
     * @param string $scope
     * @param bool $isDataObjectExists
     * @param int $dataObjectId
     * @param int $websiteId
     * @param int $websiteParam
     * @param bool $expectedResult
     * @dataProvider dataProviderCanDisplayUseDefault
     */
    public function testCanDisplayUseDefault(
        $scope,
        $isDataObjectExists,
        $dataObjectId,
        $websiteId,
        $websiteParam,
        $expectedResult
    ) {
        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->willReturnSelf();

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn(true);

        $this->element->expects($this->any())
            ->method('getScope')
            ->willReturn($scope);

        $this->form->expects($this->any())
            ->method('getDataObject')
            ->willReturn($isDataObjectExists ? $this->attribute : null);

        if ($isDataObjectExists) {
            $this->attribute->expects($this->once())
                ->method('getId')
                ->willReturn($dataObjectId);

            $this->attribute->expects($this->any())
                ->method('getWebsite')
                ->willReturn($this->website);

            $this->website->expects($this->any())
                ->method('getId')
                ->willReturn($websiteId);

            $this->request->expects($this->any())
                ->method('getParam')
                ->with('website')
                ->willReturn($websiteParam);
        }

        $this->element->setForm($this->form);
        $this->model->render($this->element);
        $this->assertEquals($expectedResult, $this->model->canDisplayUseDefault());
    }

    /**
     * 1. Scope
     * 2. 'Is DataObject Exists' flag
     *
     * Expected result
     *
     * @return array
     */
    public function dataProviderCanDisplayUseDefault()
    {
        return [
            ['global', false, 0, 0, 0, false],
            [null, false, 0, 0, 0, false],
            ['website', true, 0, 0, 0, false],
            ['website', true, 1, 0, 0, false],
            ['website', true, 1, 1, 0, false],
            ['website', true, 1, 1, 1, true],
        ];
    }
}
