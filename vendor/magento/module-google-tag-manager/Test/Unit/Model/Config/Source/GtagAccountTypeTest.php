<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Test\Unit\Model\Config\Source;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GoogleTagManager\Model\Config\TagManagerConfig as Helper;
use Magento\GoogleTagManager\Model\Config\Source\GtagAccountType;
use PHPUnit\Framework\TestCase;

class GtagAccountTypeTest extends TestCase
{
    /** @var GtagAccountType */
    protected $accountType;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->accountType = $this->objectManagerHelper->getObject(
            GtagAccountType::class
        );
    }

    public function testToOptionArray()
    {
        $options =  [
            [
                'value' => Helper::TYPE_ANALYTICS4,
                'label' => __('Google Analytics4')
            ],
            [
                'value' => Helper::TYPE_TAG_MANAGER,
                'label' => __('Google Tag Manager')
            ],
        ];
        $this->assertEquals($options, $this->accountType->toOptionArray());
    }
}
