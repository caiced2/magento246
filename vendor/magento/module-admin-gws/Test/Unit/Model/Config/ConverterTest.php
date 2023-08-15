<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Test\Unit\Model\Config;

use Magento\AdminGws\Model\Config\Converter;
use PHPUnit\Framework\TestCase;

class ConverterTest extends TestCase
{
    /**
     * @var Converter
     */
    protected $_model;

    /**
     * @var string
     */
    protected $_fixturePath;

    protected function setUp(): void
    {
        $this->_model = new Converter();
        $this->_fixturePath = realpath(__DIR__) . '/_files/';
    }

    public function testConvert()
    {
        $dom = new \DOMDocument();
        $dom->load($this->_fixturePath . 'adminGws.xml');
        $actual = $this->_model->convert($dom);
        $expected = require $this->_fixturePath . 'adminGws.php';
        $this->assertEquals($expected, $actual);
    }
}
