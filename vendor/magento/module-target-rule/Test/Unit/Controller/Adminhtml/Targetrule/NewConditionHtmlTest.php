<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Controller\Adminhtml\Targetrule;

use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\TargetRule\Controller\Adminhtml\Targetrule\NewConditionHtml;

class NewConditionHtmlTest extends AbstractTest
{
    /**
     * @var NewConditionHtml
     */
    protected $controller;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new NewConditionHtml(
            $this->contextMock,
            $this->registryMock,
            $this->dateMock
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $conditionMock = $this->getMockForConditionsHtmlAction(
            AbstractCondition::class,
            'conditions'
        );

        $this->requestMock
            ->expects($this->any())
            ->method('getParam')
            ->willReturnMap([
                ['id', null, 123],
                ['type', null, 'foo|bar'],
                ['form', null, 'form value'],
            ]);

        $conditionMock
            ->expects($this->atLeastOnce())
            ->method('asHtmlRecursive')
            ->willReturn('Result HTML');
        $conditionMock
            ->expects($this->atLeastOnce())
            ->method('setJsFormObject')
            ->with('form value');

        $this->responseMock
            ->expects($this->once())
            ->method('setBody')
            ->with('Result HTML');

        $this->controller->execute();
    }
}
