<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Model\Customer;

use Magento\CustomerCustomAttributes\Model\Customer\TemporaryFileSessionStorage;
use Magento\Framework\Session\SessionManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test temporary files session storage
 */
class TemporaryFileSessionStorageTest extends TestCase
{
    /**
     * @var SessionManagerInterface
     */
    private $session;

    /**
     * @var TemporaryFileSessionStorage
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->session = $this->getMockBuilder(SessionManagerInterface::class)
            ->addMethods(
                [
                    'setData',
                    'getData',
                    'unsetData',
                ]
            )
            ->getMockForAbstractClass();
        $this->model = new TemporaryFileSessionStorage(
            $this->session,
            'customer'
        );
    }

    /**
     * Test get() method
     */
    public function testGet(): void
    {
        $value = [
            'customer' => [
                'attr' => 'i/m/image.jpeg'
            ]
        ];
        $this->session->expects($this->once())
            ->method('getData')
            ->with('_tmp_files')
            ->willReturn($value);
        $this->assertEquals($value, $this->model->get());
    }

    /**
     * Test set() method
     */
    public function testSet(): void
    {
        $value = [
            'customer' => [
                'attr' => 'i/m/image.jpeg'
            ]
        ];
        $this->session->expects($this->once())
            ->method('setData')
            ->with('_tmp_files', $value);
        $this->model->set($value);
    }

    /**
     * Test clean() method
     */
    public function testClean(): void
    {
        $this->session->expects($this->once())
            ->method('unsetData')
            ->with('_tmp_files');
        $this->model->clean();
    }
}
