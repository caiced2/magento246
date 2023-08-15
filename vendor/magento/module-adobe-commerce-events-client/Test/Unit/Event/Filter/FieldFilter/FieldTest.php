<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Filter\FieldFilter;

use Magento\AdobeCommerceEventsClient\Event\Filter\FieldFilter\Field;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see Field class
 */
class FieldTest extends TestCase
{
    /**
     * @return void
     */
    public function testGetPath()
    {
        $parentMock = $this->createMock(Field::class);
        $parentMock->expects(self::once())
            ->method('getPath')
            ->willReturn('parent');
        $child = new Field('test', $parentMock);

        self::assertEquals('parent.test', $child->getPath());
        self::assertEquals('parent.test', $child->getPath());
        self::assertEquals('parent.test', $child->getPath());
    }

    /**
     * @return void
     */
    public function testGetPathOfNestedParent()
    {
        $root = new Field('root');
        $level1 = new Field('level1', $root);
        $level2 = new Field('level2', $level1);
        $level3 = new Field('level3', $level2);

        self::assertEquals('root', $root->getPath());
        self::assertEquals('root.level1', $level1->getPath());
        self::assertEquals('root.level1.level2', $level2->getPath());
        self::assertEquals('root.level1.level2.level3', $level3->getPath());
    }
}
