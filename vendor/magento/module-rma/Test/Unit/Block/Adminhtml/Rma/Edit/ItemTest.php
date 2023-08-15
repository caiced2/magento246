<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Block\Adminhtml\Rma\Edit;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Escaper;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Url;
use Magento\Framework\View\Layout;
use Magento\Rma\Block\Adminhtml\Rma\Edit\Item;
use Magento\Rma\Helper\Data;
use Magento\Rma\Model\Item\Form;
use Magento\Sales\Model\Order\ItemFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ItemTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $formFactoryMock;

    /**
     * @var MockObject
     */
    protected $coreRegistryMock;

    /**
     * @var MockObject
     */
    protected $contextMock;

    /**
     * @var MockObject
     */
    protected $rmaDataMock;

    /**
     * @var MockObject
     */
    protected $itemFormFactoryMock;

    /**
     * @var MockObject
     */
    protected $itemFactoryMock;

    /**
     * @var Item
     */
    protected $item;

    /**
     * @var MockObject
     */
    protected $layoutMock;

    /**
     * @var MockObject
     */
    protected $escaperMock;

    /**
     * @var MockObject
     */
    protected $urlBuilderMock;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->formFactoryMock = $this->createPartialMock(FormFactory::class, ['create']);
        $this->coreRegistryMock = $this->createMock(Registry::class);
        $this->contextMock = $this->createPartialMock(
            Context::class,
            ['getLayout', 'getEscaper', 'getUrlBuilder']
        );
        $this->escaperMock = $this->createMock(Escaper::class);
        $this->layoutMock = $this->createPartialMock(Layout::class, ['createBlock']);
        $this->urlBuilderMock = $this->createMock(Url::class);
        $this->contextMock->expects($this->any())
            ->method('getLayout')
            ->willReturn($this->layoutMock);
        $this->contextMock->expects($this->any())
            ->method('getEscaper')
            ->willReturn($this->escaperMock);
        $this->contextMock->expects($this->any())
            ->method('getUrlBuilder')
            ->willReturn($this->urlBuilderMock);
        $this->rmaDataMock = $this->createMock(Data::class);
        $this->itemFormFactoryMock = $this->createPartialMock(\Magento\Rma\Model\Item\FormFactory::class, ['create']);
        $this->itemFactoryMock = $this->createPartialMock(ItemFactory::class, ['create']);

        $this->item = $objectManager->getObject(
            Item::class,
            [
                'formFactory' => $this->formFactoryMock,
                'registry' => $this->coreRegistryMock,
                'context' => $this->contextMock,
                'rmaData' => $this->rmaDataMock,
                'itemFormFactory' => $this->itemFormFactoryMock,
                'itemFactory' => $this->itemFactoryMock,
            ]
        );
    }

    public function testInitForm()
    {
        $htmlPrefixId = 1;

        $item = $this->createMock(\Magento\Rma\Model\Item::class);

        $customerForm = $this->createPartialMock(
            Form::class,
            ['setEntity', 'setFormCode', 'initDefaultValues', 'getUserAttributes']
        );
        $customerForm->expects($this->any())
            ->method('setEntity')->willReturnSelf();
        $customerForm->expects($this->any())
            ->method('setFormCode')->willReturnSelf();
        $customerForm->expects($this->any())
            ->method('initDefaultValues')->willReturnSelf();
        $customerForm->expects($this->any())
            ->method('getUserAttributes')
            ->willReturn([]);

        $this->itemFormFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($customerForm);

        $this->coreRegistryMock->expects($this->any())
            ->method('registry')
            ->with('current_rma_item')
            ->willReturn($item);

        $fieldsetMock = $this->createMock(Fieldset::class);

        $formMock = $this->getMockBuilder(\Magento\Framework\Data\Form::class)->addMethods(
            ['setHtmlIdPrefix', 'setParent', 'setBaseUrl']
        )
            ->onlyMethods(['addFieldset', 'setValues'])
            ->disableOriginalConstructor()
            ->getMock();
        $formMock->expects($this->once())
            ->method('setHtmlIdPrefix')
            ->with($htmlPrefixId . '_rma')
            ->willReturn($htmlPrefixId);
        $formMock->expects($this->any())
            ->method('addFieldset')
            ->willReturn($fieldsetMock);
        $this->formFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($formMock);

        $blockMock = $this->createMock(Button::class);
        $blockMock->expects($this->any())
            ->method('setData')->willReturnSelf();

        $this->layoutMock->expects($this->any())
            ->method('createBlock')
            ->with(Button::class)
            ->willReturn($blockMock);
        $this->item->setHtmlPrefixId($htmlPrefixId);
        $result = $this->item->initForm();
        $this->assertInstanceOf(Item::class, $result);
    }
}
