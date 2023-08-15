<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Model;

use Magento\CatalogStaging\Model\VersionTables;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class VersionTablesTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $metadataMock;

    public function testGetVersionTables()
    {
        $versionTables = ['test_table' => 'test_table'];
        $model = new VersionTables(['version_tables' => $versionTables]);
        $this->assertEquals($versionTables, $model->getVersionTables());
    }
}
