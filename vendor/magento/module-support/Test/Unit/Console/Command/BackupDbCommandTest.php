<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Console\Command;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Console\Cli;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Console\Command\BackupDbCommand;
use Magento\Support\Helper\Shell;
use Magento\Support\Model\Backup\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BackupDbCommandTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var Shell|MockObject
     */
    private $shellHelper;

    /**
     * @var Config|MockObject
     */
    private $backupConfig;

    /**
     * @var DeploymentConfig|MockObject
     */
    private $deploymentConfig;

    /**
     * @var BackupDbCommand|MockObject
     */
    private $model;

    /**
     * @return void
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
        $this->deploymentConfig = $this->getMockBuilder(DeploymentConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = $this->objectManagerHelper->getObject(
            BackupDbCommand::class,
            [
                'shellHelper' => $this->shellHelper,
                'backupConfig' => $this->backupConfig,
                'deploymentConfig' => $this->deploymentConfig,
                'outputPath' => 'var/output/path',
                'backupName' => 'backup_name'
            ]
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        // @codingStandardsIgnoreStart
        $backupCommand = '(nice -n 15  -uroot -hlocalhost --port 3306 -p"abc!123$" magento --force --triggers --single-transaction --opt --skip-lock-tables --skip-extended-insert  customer_entity customer_entity_varchar customer_address_entity customer_address_entity_varchar customer_grid_flat quote quote_address sales_order sales_order_address sales_order_grid |  -r \'while ($line=fgets(STDIN)) {
    if (preg_match("/(^INSERT INTO\s+\S+\s+VALUES\s+)\((.*)\);$/",$line,$matches)) {
        $row = str_getcsv($matches[2],",","\x27");
        foreach($row as $key => $field) {
            if ($field == "NULL") {
                continue;
            } elseif (preg_match("/[A-Z]/i", $field)) {
                $field = md5($field . rand());
            } elseif (preg_match("/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/", $field)) {
                $field = date("Y-m-d H:i:s");
            } elseif (preg_match("/\d{4}-\d{2}-\d{2}/", $field)) {
                $field = date("Y-m-d");
            }
            $row[$key] = "\x27" . $field . "\x27";
        }
        echo $matches[1] . "(" . implode(",", $row) . ");\n";
        continue;
    }
    echo $line;
};\'; nice -n 15  --no-data -uroot -hlocalhost --port 3306 -p"abc!123$" magento --force --triggers --single-transaction --opt --skip-lock-tables  magento_logging_event magento_logging_event_changes report_event report_viewed_product_index support_backup support_backup_item 2>/dev/null; nice -n 15  -uroot -hlocalhost --port 3306 -p"abc!123$" magento --force --triggers --single-transaction --opt --skip-lock-tables  --ignore-table=\'magento\'.\'customer_entity\' --ignore-table=\'magento\'.\'customer_entity_varchar\' --ignore-table=\'magento\'.\'customer_address_entity\' --ignore-table=\'magento\'.\'customer_address_entity_varchar\' --ignore-table=\'magento\'.\'customer_grid_flat\' --ignore-table=\'magento\'.\'quote\' --ignore-table=\'magento\'.\'quote_address\' --ignore-table=\'magento\'.\'sales_order\' --ignore-table=\'magento\'.\'sales_order_address\' --ignore-table=\'magento\'.\'sales_order_grid\' --ignore-table=\'magento\'.\'magento_logging_event\' --ignore-table=\'magento\'.\'magento_logging_event_changes\' --ignore-table=\'magento\'.\'report_event\' --ignore-table=\'magento\'.\'report_viewed_product_index\' --ignore-table=\'magento\'.\'support_backup\' --ignore-table=\'magento\'.\'support_backup_item\';) |  -e \'s/DEFINER[ ]*=[ ]*[^*]*\*/\*/; /^Warning: Using a password/d\' |  > var/output/path/backup_name.sql.gz';
        // @codingStandardsIgnoreEnd
        $inputInterface = $this->getMockBuilder(InputInterface::class)
            ->getMockForAbstractClass();
        $outputInterface = $this->getMockBuilder(OutputInterface::class)
            ->getMockForAbstractClass();

        $this->shellHelper->expects($this->any())
            ->method('setRootWorkingDirectory');
        $this->shellHelper->expects($this->any())
            ->method('getUtility')
            ->willReturnMap([
                ['nice', 'nice'],
                ['tar', 'tar']
            ]);
        $this->shellHelper->expects($this->atLeastOnce())
            ->method('execute')
            ->with($backupCommand)
            ->willReturn($backupCommand);
        $this->backupConfig->expects($this->any())
            ->method('getBackupFileExtension')
            ->with('db')
            ->willReturn('sql.gz');
        $this->deploymentConfig->expects($this->any())
            ->method('get')
            ->withConsecutive(
                [ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX],
                [ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX],
                [ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX],
                [ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX],
                [ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX],
                [ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX],
                [ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX],
                [ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX],
                [ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX],
                [ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX],
                [ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTION_DEFAULT]
            )
            ->willReturnOnConsecutiveCalls(
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                [
                    'host' => 'localhost:3306',
                    'dbname' => 'magento',
                    'username' => 'root',
                    'password' => 'abc!123$',
                    'model' => 'mysql4',
                    'engine' => 'innodb',
                    'initStatements' => 'SET NAMES utf8;',
                    'active' => '1'
                ]
            );

        $this->assertEquals(
            Cli::RETURN_SUCCESS,
            $this->model->run($inputInterface, $outputInterface)
        );
    }
}
