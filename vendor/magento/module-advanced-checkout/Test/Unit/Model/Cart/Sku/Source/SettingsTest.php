<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Test\Unit\Model\Cart\Sku\Source;

use Magento\AdvancedCheckout\Model\Cart\Sku\Source\Settings;
use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase
{
    /**
     * @var Settings
     */
    private $model;

    protected function setUp(): void
    {
        $this->model = new Settings();
    }

    public function testToOptionArray()
    {
        $expectedResult = [
            ['label' => __('Yes, for Specified Customer Groups'), 'value' => Settings::YES_SPECIFIED_GROUPS_VALUE],
            ['label' => __('Yes, for Everyone'), 'value' => Settings::YES_VALUE],
            ['label' => __('No'), 'value' => Settings::NO_VALUE]
        ];
        $this->assertEquals($expectedResult, $this->model->toOptionArray());
    }
}
