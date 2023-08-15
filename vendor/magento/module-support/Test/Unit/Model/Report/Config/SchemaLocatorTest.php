<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Config;

use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Model\Report\Config\SchemaLocator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SchemaLocatorTest extends TestCase
{
    /**
     * @var SchemaLocator
     */
    protected $schemaLocator;

    protected function setUp(): void
    {
        /** @var $objectManagerHelper */
        $objectManagerHelper = new ObjectManagerHelper($this);

        /** @var Reader|MockObject $moduleReaderMock */
        $moduleReaderMock = $this->createMock(Reader::class);
        $moduleReaderMock->expects($this->once())
            ->method('getModuleDir')
            ->with('etc', 'Magento_Support')
            ->willReturn('schema_dir');

        $this->schemaLocator = $objectManagerHelper->getObject(
            SchemaLocator::class,
            [
                'moduleReader' => $moduleReaderMock
            ]
        );
    }

    public function testGetSchema()
    {
        $this->assertEquals('schema_dir/report.xsd', $this->schemaLocator->getSchema());
    }

    public function testGetPerFileSchema()
    {
        $this->assertNull($this->schemaLocator->getPerFileSchema());
    }
}
