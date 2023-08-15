<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Helper;

use Magento\Eav\Model\Entity\Attribute\Config;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rma\Helper\Eav;
use PHPUnit\Framework\TestCase;

class EavTest extends TestCase
{
    /**
     * @var Eav
     */
    protected $_model;

    protected function setUp(): void
    {
        $helper = new ObjectManager($this);
        $collectionFactory = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $attributeConfig = $this->createMock(Config::class);
        $this->_model = $helper->getObject(
            Eav::class,
            [
                'collectionFactory' => $collectionFactory,
                'attributeConfig' => $attributeConfig,
                'context' => $this->createMock(Context::class)
            ]
        );
    }

    /**
     * @param $validateRules
     * @param array $additionalClasses
     * @internal param array $attributeValidateRules
     * @dataProvider getAdditionalTextElementClassesDataProvider
     */
    public function testGetAdditionalTextElementClasses($validateRules, $additionalClasses)
    {
        $attributeMock = new DataObject(['validate_rules' => $validateRules]);
        $this->assertEquals($this->_model->getAdditionalTextElementClasses($attributeMock), $additionalClasses);
    }

    /**
     * @return array
     */
    public function getAdditionalTextElementClassesDataProvider()
    {
        return [
            [[], []],
            [['min_text_length' => 10], ['validate-length', 'minimum-length-10']],
            [['max_text_length' => 20], ['validate-length', 'maximum-length-20']],
            [
                ['min_text_length' => 10, 'max_text_length' => 20],
                ['validate-length', 'minimum-length-10', 'maximum-length-20']
            ]
        ];
    }
}
