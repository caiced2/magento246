<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model\Plugin;

use Magento\CustomerImportExport\Model\Import\Customer;
use Magento\Reward\Model\Plugin\ImportCustomer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImportCustomerTest extends TestCase
{
    /** @var  ImportCustomer */
    private $plugin;

    /** @var  MockObject|Customer */
    private $importCustomer;

    protected function setUp(): void
    {
        $this->plugin = new ImportCustomer();
        $this->importCustomer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testAfterGetIdentities()
    {
        $previousColumns  = [
            'column_name_1',
            'column_name_2',
        ];
        $columnNames = $this->plugin->afterGetValidColumnNames($this->importCustomer, $previousColumns);
        $this->assertCount(4, $columnNames);
    }
}
