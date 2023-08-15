<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Filter\FieldFilter;

use Magento\AdobeCommerceEventsClient\Event\Filter\FieldFilter\FieldConverter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see FieldConverter class
 */
class FieldConverterTest extends TestCase
{
    /**
     * @var FieldConverter
     */
    private FieldConverter $fieldConvertor;

    protected function setUp(): void
    {
        $this->fieldConvertor = new FieldConverter();
    }

    /**
     * @return void
     */
    public function testConvert()
    {
        $fields = $this->fieldConvertor->convert([
            'entity_id',
            'product.sku',
            'product.qty',
        ]);

        self::assertEquals(2, count($fields));
        self::assertEquals('entity_id', $fields[0]->getName());
        self::assertEquals('product', $fields[1]->getName());
        self::assertFalse($fields[1]->isArray());
        self::assertEquals(2, count($fields[1]->getChildren()));
    }

    /**
     * @return void
     */
    public function testConvertArrayFields()
    {
        $fields = $this->fieldConvertor->convert([
            ['test'],
            'product[].sku',
            'product[].qty',
            'product[].name',
        ]);

        self::assertEquals(1, count($fields));
        self::assertEquals('product', $fields[0]->getName());
        self::assertTrue($fields[0]->isArray());
        self::assertEquals(3, count($fields[0]->getChildren()));
    }
}
