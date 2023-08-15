<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WebsiteRestriction\Test\Unit\Model\Config;

use Magento\WebsiteRestriction\Model\Config\Converter;
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
    protected $_filePath;

    protected function setUp(): void
    {
        $this->_model = new Converter();
        $this->_filePath = realpath(__DIR__) . '/_files/';
    }

    public function testConvert()
    {
        $dom = new \DOMDocument();
        $dom->load($this->_filePath . 'webrestrictions.xml');
        $actual = $this->_model->convert($dom);
        $expected = require $this->_filePath . 'webrestrictions.php';
        $this->assertEquals($expected, $actual);
    }
}
