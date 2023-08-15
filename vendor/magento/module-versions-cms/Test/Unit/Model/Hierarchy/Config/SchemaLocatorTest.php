<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Test\Unit\Model\Hierarchy\Config;

use Magento\Framework\Module\Dir\Reader;
use Magento\VersionsCms\Model\Hierarchy\Config\SchemaLocator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SchemaLocatorTest extends TestCase
{
    /**
     * @var SchemaLocator
     */
    protected $_model;

    /**
     * @var MockObject
     */
    protected $_modulesReaderMock;

    protected function setUp(): void
    {
        $this->_modulesReaderMock = $this->createMock(Reader::class);

        $this->_modulesReaderMock->expects(
            $this->once()
        )->method(
            'getModuleDir'
        )->with(
            'etc',
            'Magento_VersionsCms'
        )->willReturn(
            'some_path'
        );

        $this->_model = new SchemaLocator($this->_modulesReaderMock);
    }

    /**
     * @covers \Magento\VersionsCms\Model\Hierarchy\Config\SchemaLocator::getSchema
     */
    public function testGetSchema()
    {
        $expectedSchemaPath = 'some_path/menu_hierarchy_merged.xsd';
        $this->assertEquals($expectedSchemaPath, $this->_model->getSchema());
    }

    /**
     * @covers \Magento\VersionsCms\Model\Hierarchy\Config\SchemaLocator::getPerFileSchema
     */
    public function testGetPerFileSchema()
    {
        $expectedSchemaPath = 'some_path/menu_hierarchy.xsd';
        $this->assertEquals($expectedSchemaPath, $this->_model->getPerFileSchema());
    }
}
