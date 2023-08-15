<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Attributes;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Model\Report\Group\Attributes\DataFormatter;
use PHPUnit\Framework\TestCase;

class DataFormatterTest extends TestCase
{
    /**
     * @var DataFormatter
     */
    protected $dataFormatter;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;

    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->dataFormatter = $this->objectManagerHelper->getObject(
            DataFormatter::class
        );
    }

    /**
     * @param string|null $className
     * @param string $expectedResult
     *
     * @dataProvider prepareModelValueDataProvider
     */
    public function testPrepareModelValue($className, $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->dataFormatter->prepareModelValue($className));
    }

    /**
     * @return array
     */
    public function prepareModelValueDataProvider()
    {
        return [
            ['className' => null, 'expectedResult' => ''],
            [
                'className' => 'Some\Model\Class\Name',
                'expectedResult' => 'Some\Model\Class\Name' . "\n" . '{Some/Model/Class/Name.php}'
            ]
        ];
    }
}
