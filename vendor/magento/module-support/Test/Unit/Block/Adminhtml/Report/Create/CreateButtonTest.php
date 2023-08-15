<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Block\Adminhtml\Report\Create;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Block\Adminhtml\Report\Create\CreateButton;
use PHPUnit\Framework\TestCase;

class CreateButtonTest extends TestCase
{
    /**
     * @var CreateButton
     */
    protected $createButton;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;

    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->createButton = $this->objectManagerHelper->getObject(
            CreateButton::class
        );
    }

    public function testGetButtonData()
    {
        $buttonData = [
            'label' => __('Create'),
            'class' => 'primary'
        ];

        $this->assertEquals($buttonData, $this->createButton->getButtonData());
    }
}
