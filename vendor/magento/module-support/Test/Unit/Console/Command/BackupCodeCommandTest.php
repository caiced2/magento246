<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Console\Command;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Console\Command\BackupCodeCommand;
use Magento\Support\Helper\Shell;
use Magento\Support\Model\Backup\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BackupCodeCommandTest extends TestCase
{
    /**
     * Application Root
     */
    const APP_ROOT_PATH = '/app/root/';

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var Shell|MockObject
     */
    protected $shellHelper;

    /**
     * @var Config|MockObject
     */
    protected $backupConfig;

    /**
     * @var BackupCodeCommand
     */
    protected $model;

    /**
     * @inheirtDoc
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->shellHelper = $this->getMockBuilder(Shell::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->backupConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = $this->objectManagerHelper->getObject(
            BackupCodeCommand::class,
            [
                'shellHelper' => $this->shellHelper,
                'backupConfig' => $this->backupConfig,
                'outputPath' => 'var/output/path',
                'backupName' => 'backup_name'
            ]
        );
    }

    /**
     * @dataProvider existingBackupListProvider
     * @param $existingBackupList
     * @return void
     * @throws \Exception
     */
    public function testExecute($existingBackupList): void
    {
        $expectedBackupCmd = 'nice -n 15 tar -czhf var/output/path/backup_name.tar.gz '
            . implode(' ', $existingBackupList);
        $inputInterface = $this->getMockBuilder(InputInterface::class)
            ->getMockForAbstractClass();
        $outputInterface = $this->getMockBuilder(OutputInterface::class)
            ->getMockForAbstractClass();
        $this->shellHelper->expects($this->any())->method('setRootWorkingDirectory');
        $this->shellHelper->expects($this->any())->method('getUtility')->willReturnMap([
            ['nice', 'nice'],
            ['tar', 'tar']
        ]);
        $this->shellHelper->expects($this->atLeastOnce())->method('execute')->with($expectedBackupCmd)
            ->willReturn($expectedBackupCmd);
        $this->shellHelper->method('pathExists')
            ->willReturnCallback(function ($param) use ($existingBackupList) {
                if (in_array($param, $existingBackupList)) {
                    return true;
                }
                return false;
            });
        $this->backupConfig->expects($this->any())->method('getBackupFileExtension')->with('code')
            ->willReturn('tar.gz');
        $outputInterface
            ->method('writeln')
            ->withConsecutive(
                [$expectedBackupCmd],
                [$expectedBackupCmd],
                ['Code dump was created successfully']
            );

        $this->model->run($inputInterface, $outputInterface);
    }

    /**
     * @return array
     */
    public function existingBackupListProvider(): array
    {
        return [
            [['vendor']],
            [['app', 'bin', 'composer.*']],
            [['bin', 'vendor']],
            [
                [
                    'app',
                    'bin',
                    'composer.*',
                    'dev',
                    '*.php',
                    'lib',
                    'pub/*.php'
                ]
            ]
        ];
    }
}
