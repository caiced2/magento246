<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Backup\Item;

use Magento\Support\Model\Backup\Item\Code;
use Magento\Support\Test\Unit\Model\Backup\AbstractItemTest;

class CodeTest extends AbstractItemTest
{
    /**
     * @return void
     */
    protected function createTestedItem()
    {
        $this->item = $this->objectManagerHelper->getObject(
            Code::class,
            [
                'backupFactory' => $this->backupFactoryMock,
                'shellHelper' => $this->shellHelperMock,
                'cmdPhpFactory' => $this->cmdPhpFactoryMock,
                'filesystem' => $this->filesystemMock,
                'resource' => $this->resourceMock
            ]
        );
    }

    /**
     * @return void
     */
    protected function setCmdScriptName()
    {
        $this->cmdPhpMock->expects($this->once())
            ->method('setScriptName')
            ->with('bin/magento support:backup:code');
    }
}
