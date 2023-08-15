<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Logs;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Support\Model\Report\Group\Logs\LogFilesData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class AbstractLogsSectionTest extends TestCase
{
    /**
     * @var LogFilesData|MockObject
     */
    protected $logFilesDataMock;

    /**
     * @var ObjectManager
     */
    protected $objectManagerHelper;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManager($this);
        $this->logFilesDataMock = $this->getMockBuilder(LogFilesData::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
