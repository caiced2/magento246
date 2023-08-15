<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Collector;

use Magento\AdobeCommerceEventsClient\Event\Collector\MethodFilter;
use Magento\Framework\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the @see MethodFilter Class
 */
class MethodFilterTest extends TestCase
{
    /**
     * @throws InvalidArgumentException
     */
    public function testIsExclude(): void
    {
        $methodFilter = new MethodFilter([
            'method1',
            'method2',
            '/^get.*/',
        ]);

        self::assertTrue($methodFilter->isExcluded('method1'));
        self::assertTrue($methodFilter->isExcluded('method2'));
        self::assertTrue($methodFilter->isExcluded('getName'));
        self::assertFalse($methodFilter->isExcluded('nameGet'));
        self::assertFalse($methodFilter->isExcluded('method'));
        self::assertFalse($methodFilter->isExcluded('method3'));
    }

    public function testIsExcludeWrongExcludeMethods(): void
    {
        self::expectException(InvalidArgumentException::class);
        $methodFilter = new MethodFilter([
            ['wrong_type_of_method_parameter']
        ]);

        self::assertTrue($methodFilter->isExcluded('method1'));
    }
}
