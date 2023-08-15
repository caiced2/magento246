<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Collector;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use stdClass;

/**
 * Abstract class to store reusable methods
 */
abstract class AbstractCollectorTest extends TestCase
{
    /**
     * Creates SplFileInfo Mock object
     *
     * @param string $extension
     * @param bool $isDir
     * @return MockObject
     */
    protected function createFileInfoMock(string $extension = 'php', bool $isDir = false): MockObject
    {
        $fileMockOne = $this->createMock(SplFileInfo::class);
        $fileMockOne->expects(self::once())
            ->method('isDir')
            ->willReturn($isDir);
        $fileMockOne->expects(self::any())
            ->method('getExtension')
            ->willReturn($extension);

        return $fileMockOne;
    }

    /**
     * Setup methods required to mock an iterator
     *
     * @param MockObject $iteratorMock The mock to attach the iterator methods to
     * @param array $items The mock data we're going to use with the iterator
     * @return MockObject The iterator mock
     */
    protected function mockIterator(MockObject $iteratorMock, array $items): MockObject
    {
        $iteratorData = new stdClass();
        $iteratorData->array = $items;
        $iteratorData->position = 0;

        $iteratorMock->method('rewind')
            ->willReturnCallback(
                static function () use ($iteratorData) {
                    $iteratorData->position = 0;
                }
            );

        $iteratorMock->method('current')
            ->willReturnCallback(
                static function () use ($iteratorData) {
                    return $iteratorData->array[$iteratorData->position];
                }
            );

        $iteratorMock->method('key')
            ->willReturnCallback(
                static function () use ($iteratorData) {
                    return $iteratorData->position;
                }
            );

        $iteratorMock->expects($this->any())
            ->method('next')
            ->willReturnCallback(
                static function () use ($iteratorData) {
                    $iteratorData->position++;
                }
            );

        $iteratorMock->expects($this->any())
            ->method('valid')
            ->willReturnCallback(
                static function () use ($iteratorData) {
                    return isset($iteratorData->array[$iteratorData->position]);
                }
            );

        return $iteratorMock;
    }
}
