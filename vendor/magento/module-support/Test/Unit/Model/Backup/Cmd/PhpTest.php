<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Backup\Cmd;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Model\Backup\Cmd\Php;
use PHPUnit\Framework\TestCase;

class PhpTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var Php
     */
    protected $php;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->php = $this->objectManagerHelper->getObject(Php::class);
    }

    /**
     * @param string|null $scriptInterpreter
     * @return void
     * @dataProvider scriptInterpreterDataProvider
     */
    public function testSetAndGetScriptInterpreter($scriptInterpreter)
    {
        $this->php->setScriptInterpreter($scriptInterpreter);
        $this->assertSame($scriptInterpreter, $this->php->getScriptInterpreter());
    }

    /**
     * @return array
     */
    public function scriptInterpreterDataProvider()
    {
        return [
            ['scriptInterpreter' => null],
            ['scriptInterpreter' => ''],
            ['scriptInterpreter' => '/bin/php']
        ];
    }

    /**
     * @param string|null $scriptName
     * @return void
     * @dataProvider scriptNameDataProvider
     */
    public function testSetAndGetScriptName($scriptName)
    {
        $this->php->setScriptName($scriptName);
        $this->assertSame($scriptName, $this->php->getScriptName());
    }

    /**
     * @return array
     */
    public function scriptNameDataProvider()
    {
        return [
            ['scriptInterpreter' => null],
            ['scriptInterpreter' => ''],
            ['scriptInterpreter' => 'bin/magento support:backup:db']
        ];
    }

    /**
     * @param string $output
     * @return void
     * @dataProvider redirectOutputDataProvider
     */
    public function testSetAndGetRedirectOutput($output)
    {
        $this->php->setRedirectOutput($output);
        $this->assertSame($output, $this->php->getRedirectOutput());
    }

    /**
     * @return array
     */
    public function redirectOutputDataProvider()
    {
        return [
            ['output' => null],
            ['output' => ''],
            ['output' => '/dev/null'],
        ];
    }

    /**
     * @param string $scriptInterpreter
     * @param string $scriptName
     * @param string|null $redirectOutput
     * @param bool $argsWithKeys
     * @param string $equalSeparator
     * @param array $data
     * @param string $expectedResult
     * @return void
     * @dataProvider generateDataProvider
     */
    public function testGenerate(
        $scriptInterpreter,
        $scriptName,
        $redirectOutput,
        $argsWithKeys,
        $equalSeparator,
        $data,
        $expectedResult
    ) {
        $this->php->setScriptInterpreter($scriptInterpreter);
        $this->php->setScriptName($scriptName);
        $this->php->setRedirectOutput($redirectOutput);
        $this->php->setData($data);

        $this->assertSame($expectedResult, $this->php->generate($argsWithKeys, $equalSeparator));
    }

    /**
     * @return array
     */
    public function generateDataProvider()
    {
        return [
            [
                'scriptInterpreter' => '/bin/php',
                'scriptName' => 'bin/magento support:backup:db',
                'redirectOutput' => '/dev/null',
                'argsWithKeys' => true,
                'equalSeparator' => '=',
                'data' => ['force' => 'true'],
                'expectedResult' => '/bin/php ' . BP . '/bin/magento support:backup:db --force=true > /dev/null'
            ],
            [
                'scriptInterpreter' => '/bin/php',
                'scriptName' => 'bin/magento support:backup:db',
                'redirectOutput' => 'backup.log',
                'argsWithKeys' => true,
                'equalSeparator' => '==',
                'data' => ['force' => 'true'],
                'expectedResult' => '/bin/php ' . BP . '/bin/magento support:backup:db --force==true > backup.log'
            ],
            [
                'scriptInterpreter' => '/bin/php',
                'scriptName' => 'bin/magento support:backup:code',
                'redirectOutput' => '/dev/null',
                'argsWithKeys' => true,
                'equalSeparator' => '=',
                'data' => ['force' => ''],
                'expectedResult' => '/bin/php ' . BP . '/bin/magento support:backup:code -force > /dev/null'
            ],
            [
                'scriptInterpreter' => '/bin/php',
                'scriptName' => 'bin/magento support:backup:code',
                'redirectOutput' => null,
                'argsWithKeys' => false,
                'equalSeparator' => '=',
                'data' => ['force'],
                'expectedResult' => '/bin/php ' . BP . '/bin/magento support:backup:code force'
            ],
            [
                'scriptInterpreter' => '/bin/php',
                'scriptName' => 'bin/magento support:backup:code',
                'redirectOutput' => null,
                'argsWithKeys' => false,
                'equalSeparator' => '=',
                'data' => [],
                'expectedResult' => '/bin/php ' . BP . '/bin/magento support:backup:code'
            ],
        ];
    }
}
