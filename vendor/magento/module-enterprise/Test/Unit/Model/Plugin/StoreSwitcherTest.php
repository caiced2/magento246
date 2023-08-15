<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Enterprise\Test\Unit\Model\Plugin;

use Magento\Backend\Block\Store\Switcher as StoreSwitcherBlock;
use Magento\Enterprise\Model\Plugin\StoreSwitcher as StoreSwitcherPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StoreSwitcherTest extends TestCase
{
    /**
     * @var StoreSwitcherPlugin
     */
    private $storeSwitcherPlugin;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var StoreSwitcherBlock|MockObject
     */
    private $subjectMock;

    protected function setUp(): void
    {
        $this->subjectMock = $this->getMockBuilder(StoreSwitcherBlock::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->storeSwitcherPlugin = $this->objectManagerHelper->getObject(StoreSwitcherPlugin::class);
    }

    public function testAfterGetHintUrl()
    {
        $this->assertEquals(
            StoreSwitcherPlugin::HINT_URL,
            $this->storeSwitcherPlugin->afterGetHintUrl($this->subjectMock)
        );
    }
}
