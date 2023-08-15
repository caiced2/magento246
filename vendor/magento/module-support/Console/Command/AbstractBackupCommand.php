<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Console\Command;

use Symfony\Component\Console\Command\Command;
use Magento\Support\Helper\Shell as ShellHelper;
use Magento\Support\Model\Backup\Config as BackupConfig;

/**
 * An Abstract class for support tool related commands.
 */
class AbstractBackupCommand extends Command
{
    /**
     * @var ShellHelper
     */
    protected $shellHelper;

    /**
     * @var BackupConfig
     */
    protected $backupConfig;

    /**
     * @param ShellHelper $shellHelper
     * @param BackupConfig $backupConfig
     */
    public function __construct(ShellHelper $shellHelper, BackupConfig $backupConfig)
    {
        parent::__construct();
        $this->shellHelper = $shellHelper;
        $this->backupConfig = $backupConfig;
    }
}
