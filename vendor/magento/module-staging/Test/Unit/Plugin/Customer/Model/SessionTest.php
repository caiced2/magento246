<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Plugin\Customer\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Plugin\Customer\Model\Session;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for plugin for customer session model.
 */
class SessionTest extends TestCase
{
    /**
     * Subject of testing.
     *
     * @var Session
     */
    private $subject;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var VersionManager|MockObject
     */
    private $versionManagerMock;

    /**
     * @var \Magento\Customer\Model\Session|MockObject
     */
    private $customerSessionMock;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->versionManagerMock = $this->createMock(VersionManager::class);

        $this->customerSessionMock = $this->createMock(\Magento\Customer\Model\Session::class);

        $this->subject = $this->objectManager->getObject(
            Session::class,
            [
                'versionManager' => $this->versionManagerMock
            ]
        );
    }

    /**
     * @param bool $isPreview
     *
     * @dataProvider dataProvider
     */
    public function testAroundRegenerateId($isPreview)
    {
        $closureMock = function () {
            return 'Closure executed';
        };

        $expectedResult = $closureMock();

        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn($isPreview);

        // Assertions.
        if ($isPreview) {
            $expectedResult = $this->customerSessionMock;
        }

        $this->assertEquals(
            $expectedResult,
            $this->subject->aroundRegenerateId(
                $this->customerSessionMock,
                $closureMock
            )
        );
    }

    /**
     * @param bool $isPreview
     *
     * @dataProvider dataProvider
     */
    public function testAroundDestroy($isPreview)
    {
        $closureMock = function ($options) {
            return $options;
        };

        $options = ['option' => 'value'];

        $expectedResult = $closureMock($options);

        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn($isPreview);

        // Assertions.
        if ($isPreview) {
            $expectedResult = $this->customerSessionMock;
        }

        $this->assertEquals(
            $expectedResult,
            $this->subject->aroundDestroy(
                $this->customerSessionMock,
                $closureMock,
                $options
            )
        );
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [[false], [true]];
    }
}
