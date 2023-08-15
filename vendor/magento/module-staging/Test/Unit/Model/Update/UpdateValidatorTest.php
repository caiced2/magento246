<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Update;

use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Model\Update\UpdateValidator;
use PHPUnit\Framework\TestCase;

class UpdateValidatorTest extends TestCase
{
    /** @var UpdateValidator */
    private $model;

    protected function setUp(): void
    {
        $this->model = new UpdateValidator();
    }

    public function testValidateUpdateStartedExecption()
    {
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('The Start Time of this Update cannot be changed. It\'s been already started.');
        $updateId = 1;
        $stagingData = [
            'update_id' => $updateId,
            'start_time' => date('Y-m-d H:i:s', strtotime('+1 day')),
        ];

        $updateMock = $this->createMock(UpdateInterface::class);
        $updateMock->method('getStartTime')
            ->willReturn(date('Y-m-d H:i:s', strtotime('-1 day')));
        $this->model->validateUpdateStarted($updateMock, $stagingData);
    }

    public function testValidateUpdateStarted()
    {
        $updateId = 1;
        $stagingData = [
            'update_id' => $updateId,
            'start_time' => date('Y-m-d H:i:s', strtotime('+1 year')),
        ];

        $updateMock = $this->createMock(UpdateInterface::class);
        $updateMock->method('getStartTime')
            ->willReturn(date('Y-m-d H:i:s', strtotime('+1 day')));
        $result = $this->model->validateUpdateStarted($updateMock, $stagingData);
        $this->assertNull($result);
    }

    public function testValidateParams()
    {
        $params = [
            'stagingData' => [],
            'entityData' => []
        ];

        $result = $this->model->validateParams($params);
        $this->assertNull($result);
    }

    public function testValidateWithInvalidParam()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The required parameter is "stagingData". Set parameter and try again.');
        $params = [
            'entityData' => []
        ];

        $this->model->validateParams($params);
    }
}
