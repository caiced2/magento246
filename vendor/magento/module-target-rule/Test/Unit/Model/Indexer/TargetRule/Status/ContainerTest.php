<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model\Indexer\TargetRule\Status;

use Magento\TargetRule\Model\Indexer\TargetRule\Status\Container;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    /** @var Container */
    private $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    /**
     * @covers \Magento\TargetRule\Model\Indexer\TargetRule\Status\Container::setFullReindexPassed
     */
    public function testSetFullReindexPassed()
    {
        $indexedIdString = 'indexedId';
        $this->container->setFullReindexPassed($indexedIdString);
        $this->assertTrue($this->container->isFullReindexPassed($indexedIdString));
    }

    /**
     * @covers \Magento\TargetRule\Model\Indexer\TargetRule\Status\Container::getFullReindexPassed
     * @dataProvider dataProvider
     * @param String $indexedIdString
     * @param Boolean $expectedResult
     */
    public function testGetFullReindexPassed($indexedIdString, $expectedResult)
    {
        $this->container->setFullReindexPassed('indexedId');
        $this->assertEquals($expectedResult, $this->container->getFullReindexPassed($indexedIdString));
    }

    /**
     * @covers \Magento\TargetRule\Model\Indexer\TargetRule\Status\Container::isFullReindexPassed
     * @dataProvider dataProvider
     * @param String $indexedIdString
     * @param Boolean $expectedResult
     */
    public function testIsFullReindexPassed($indexedIdString, $expectedResult)
    {
        $this->container->setFullReindexPassed('indexedId');
        $this->assertEquals($expectedResult, $this->container->isFullReindexPassed($indexedIdString));
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            'positiveTestData' => ['indexedId', true],
            'negativeTestData' => ['nonIndexedId', false],
        ];
    }
}
