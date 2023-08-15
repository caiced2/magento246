<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Test\Unit\Ui\Component\Plugin\Catalog\Product;

use Magento\AdminGws\Model\Role;
use Magento\AdminGws\Ui\Component\Plugin\Catalog\Product\MassAction;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class MassActionTest is a test for plugin Magento\AdminGws\Test\Unit\Ui\Component\Plugin\Catalog\Product
 */
class MassActionTest extends TestCase
{
    /**
     * @var Role|MockObject
     */
    private $roleMock;

    /**
     * @var MassAction
     */
    private $massAction;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->roleMock = $this->getMockBuilder(Role::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->massAction = $objectManager->getObject(
            MassAction::class,
            [
                'role' => $this->roleMock,
            ]
        );
    }

    /**
     * @param bool $expected
     * @param bool $isActionAllowed
     * @param string $actionType
     * @param int $callGetIsAllNum
     * @param bool $isAll
     * @dataProvider afterIsActionAllowedDataProvider
     */
    public function testAfterIsActionAllowed(
        $expected,
        $isActionAllowed,
        $actionType,
        $callGetIsAllNum = 0,
        $isAll = true
    ) {
        /** @var \Magento\Catalog\Ui\Component\Product\MassAction|MockObject $massActionMock */
        $massActionMock = $this->getMockBuilder(\Magento\Catalog\Ui\Component\Product\MassAction::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->roleMock->expects($this->exactly($callGetIsAllNum))
            ->method('getIsAll')
            ->willReturn($isAll);

        $this->assertEquals(
            $expected,
            $this->massAction->afterIsActionAllowed($massActionMock, $isActionAllowed, $actionType)
        );
    }

    public function afterIsActionAllowedDataProvider() : array
    {
        return [
            'other-allowed' => [true, true, 'other'],
            'other-not-allowed' => [false, false, 'other'],
            'delete-allowed' => [true, true, 'delete', 1],
            'delete-allowed-rollIsNotAll' => [false, true, 'delete', 1, false],
            'delete-not-allowed' => [false, false, 'delete'],
            'status-allowed' => [true, true, 'status', 1],
            'status-allowed-rollIsNotAll' => [false, true, 'status', 1, false],
            'status-not-allowed' => [false, false, 'status'],
            'attributes-allowed' => [true, true, 'attributes'],
            'attributes-not-allowed' => [false, false, 'attributes'],
        ];
    }
}
