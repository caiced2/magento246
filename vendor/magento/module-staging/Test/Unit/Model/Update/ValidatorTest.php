<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Update;

use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Model\Update;
use Magento\Staging\Model\Update\Validator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    /**
     * @var Validator
     */
    protected $model;

    /**
     * @var MockObject|\Magento\Staging\Model\Update
     */
    protected $entityMock;

    /**
     * @var MockObject|DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * Set Up function.
     */
    protected function setUp(): void
    {
        $this->entityMock = $this->createMock(Update::class);
        $objectManager = new ObjectManager($this);
        $this->dateTimeFactory = $this->createMock(DateTimeFactory::class);
        $this->model = $this->model = $objectManager
            ->getObject(
                Validator::class,
                ['dateTimeFactory' => $this->dateTimeFactory]
            );
    }

    public function testValidateWithEmptyName()
    {
        $this->expectException('Magento\Framework\Exception\ValidatorException');
        $this->expectExceptionMessage('The Name for Future Update needs to be selected. Select and try again.');
        $this->entityMock->expects($this->once())->method('getName')->willReturn('');
        $this->model->validateCreate($this->entityMock);
    }

    public function testValidateWithEmptyStartTime()
    {
        $this->expectException('Magento\Framework\Exception\ValidatorException');
        $this->expectExceptionMessage('The Start Time for Future Update needs to be selected. Select and try again.');
        $this->entityMock->expects($this->once())->method('getName')->willReturn('Test Update');
        $this->model->validateCreate($this->entityMock);
    }

    public function testValidateWithWrongStartDateTime()
    {
        $this->expectException('Magento\Framework\Exception\ValidatorException');
        $this->expectExceptionMessage(
            'The Future Update Start Time is invalid. It can\'t be earlier than the current time.'
        );
        $this->entityMock->expects($this->once())->method('getName')->willReturn('Test Update');
        $startDateTime = new \DateTime('-10 minutes');
        $this->entityMock->expects($this->any())
            ->method('getStartTime')
            ->willReturn($startDateTime->format("m/d/Y H:i:s"));
        $this->model->validateCreate($this->entityMock);
    }

    public function testValidateWithWrongEndDateTime()
    {
        $this->expectException('Magento\Framework\Exception\ValidatorException');
        $this->entityMock->expects($this->once())->method('getName')->willReturn('Test Update');
        $startDateTime = new \DateTime('tomorrow');
        $endDateTime = $startDateTime->sub(new \DateInterval('PT10M'));
        $this->entityMock->expects($this->any())
            ->method('getStartTime')
            ->willReturn($startDateTime->format("m/d/Y H:i:s"));
        $this->entityMock->expects($this->any())
            ->method('getEndTime')
            ->willReturn($endDateTime->format("m/d/Y H:i:s"));
        $this->model->validateCreate($this->entityMock);

        $this->expectExceptionMessage(
            "The Future Update End Time is invalid. It can't be the same time or earlier than the current time."
        );
    }

    public function testValidateWrongStartTime()
    {
        $this->expectException('Magento\Framework\Exception\ValidatorException');
        $this->entityMock->expects($this->once())->method('getName')->willReturn('Test Update');
        $startDateTime = (new \DateTime())->modify('+ 35 years');
        $this->entityMock->expects($this->exactly(3))
            ->method('getStartTime')
            ->willReturn($startDateTime->format("m/d/Y H:i:s"));
        $this->model->validateCreate($this->entityMock);

        $this->expectExceptionMessage(
            "The Future Update Start Time is invalid. It can't be later than current time + 16 years."
        );
    }

    public function testValidateWithInvalidEndTime()
    {
        $this->expectException('Magento\Framework\Exception\ValidatorException');
        $this->entityMock->expects($this->once())->method('getName')->willReturn('Test Update');
        $startDateTime = new \DateTime();
        $endTime = (new \DateTime())->modify('+ 35 years');
        $this->entityMock->expects($this->atLeastOnce())
            ->method('getStartTime')
            ->willReturn($startDateTime->format("m/d/Y H:i:s"));

        $this->entityMock->expects($this->atLeastOnce())
            ->method('getEndTime')
            ->willReturn($endTime->format("m/d/Y H:i:s"));

        $this->model->validateCreate($this->entityMock);

        $this->expectExceptionMessage(
            "The Future Update End Time is invalid. It can't be later than current time + 16 years."
        );
    }

    /**
     * Test validate Update staging with beyond maximum limit of end time.
     *
     * @throws \Exception
     * @throws ValidatorException
     */
    public function testValidateUpdateWithInvalidEndTime()
    {
        $this->expectException('Magento\Framework\Exception\ValidatorException');
        $this->entityMock->expects($this->once())->method('getName')->willReturn('Test Update');
        $startDateTime = new \DateTime();
        $endTime = (new \DateTime())->modify('+ 20 years');
        $this->entityMock->expects($this->atLeastOnce())
            ->method('getStartTime')
            ->willReturn($startDateTime->format("m/d/Y H:i:s"));

        $this->entityMock->expects($this->atLeastOnce())
            ->method('getEndTime')
            ->willReturn($endTime->format("m/d/Y H:i:s"));

        $this->model->validateUpdate($this->entityMock);

        $this->expectExceptionMessage(
            "The Future Update End Time is invalid. It can't be later than current time + 16 years."
        );
    }

    /**
     * Test validate create.
     *
     * @throws \Exception
     * @throws ValidatorException
     */
    public function testValidate()
    {
        $this->entityMock->expects($this->once())->method('getName')->willReturn('Test Update');
        $startDateTime = new \DateTime();
        $startDateTime->add(new \DateInterval('PT60S'));
        $this->entityMock->expects($this->any())
            ->method('getStartTime')
            ->willReturn($startDateTime->format("m/d/Y H:i:s"));

        $this->model->validateCreate($this->entityMock);
    }

    /**
     * Test validate update
     *
     * @throws \Exception
     * @throws ValidatorException
     */
    public function testValidateUpdate()
    {
        $this->entityMock->expects($this->once())->method('getName')->willReturn('Test Update');
        $startDateTime = new \DateTime();
        $this->entityMock->expects($this->any())
            ->method('getStartTime')
            ->willReturn($startDateTime->format("m/d/Y H:i:s"));
        $startDateTime->add(new \DateInterval('PT60S'));
        $this->entityMock->expects($this->any())
            ->method('getEndTime')
            ->willReturn($startDateTime->format('m/d/Y H:i:s'));
        $this->model->validateUpdate($this->entityMock);
    }

    /**
     * Scenario: End Time is less than current time. Exception expected
     */
    public function testValidateUpdate2()
    {
        $this->expectException('Magento\Framework\Exception\ValidatorException');
        $this->expectExceptionMessage(
            'The Future Update End Time is invalid. It can\'t be earlier than the current time.'
        );
        $this->entityMock->expects($this->once())->method('getName')->willReturn('Test Update');

        $startDateTime = new \DateTime(date('m/d/Y H:i:s'));
        $startDateTime->sub(new \DateInterval('P5D'));

        $this->entityMock->expects($this->any())
            ->method('getStartTime')
            ->willReturn($startDateTime->format("m/d/Y H:i:s"));

        $startDateTime->add(new \DateInterval('P2D'));

        $this->entityMock->expects($this->any())
            ->method('getEndTime')
            ->willReturn($startDateTime->format('m/d/Y H:i:s'));
        $this->model->validateUpdate($this->entityMock);
    }
}
