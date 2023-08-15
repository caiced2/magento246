<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogRuleStaging\Test\Unit\Block\Adminhtml\Update;

use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\CatalogRule\Api\Data\RuleInterface;
use Magento\CatalogRuleStaging\Block\Adminhtml\Update\Provider;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProviderTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $ruleRepositoryMock;

    /**
     * @var MockObject
     */
    protected $requestMock;

    /**
     * @var Provider
     */
    protected $button;

    protected function setUp(): void
    {
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->ruleRepositoryMock = $this->createMock(
            CatalogRuleRepositoryInterface::class
        );
        $this->button = new Provider($this->requestMock, $this->ruleRepositoryMock);
    }

    public function testGetRuleIdNoRule()
    {
        $this->ruleRepositoryMock->expects($this->once())
            ->method('get')
            ->willThrowException(new NoSuchEntityException(__('Smth went to exception')));
        $this->assertNull($this->button->getId());
    }

    public function testGetRuleId()
    {
        $id = 203040;
        $catalogRuleMock = $this->getMockForAbstractClass(RuleInterface::class);

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('id')
            ->willReturn($id);
        $this->ruleRepositoryMock->expects($this->once())
            ->method('get')
            ->with($id)
            ->willReturn($catalogRuleMock);
        $catalogRuleMock->expects($this->once())->method('getRuleId')->willReturn($id);

        $this->assertEquals($id, $this->button->getId());
    }
}
