<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\ScalableOms\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Magento\Framework\ForeignKey\Migration\AbstractCommand;

/**
 * @codeCoverageIgnore
 * @deprecated split database solution is deprecated and will be removed
 */
class SplitSales extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function getCommandName()
    {
        return 'setup:db-schema:split-sales';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandDescription()
    {
        return 'Move sales related tables to a separate DB server. Deprecated since 2.4.2 and will be removed';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandDefinition()
    {
        return [
            new InputOption(
                self::HOST,
                null,
                InputOption::VALUE_REQUIRED,
                'Sales DB Server host'
            ),
            new InputOption(
                self::DB_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'Sales Database Name'
            ),
            new InputOption(
                self::USER_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'Sales DB user name'
            ),
            new InputOption(
                self::PASSWORD,
                null,
                InputOption::VALUE_OPTIONAL,
                'Sales DB user passowrd'
            ),
            new InputOption(
                self::CONNECTION,
                null,
                InputOption::VALUE_OPTIONAL,
                'Sales connection name',
                'sales'
            ),
            new InputOption(
                self::RESOURCE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Sales resource name',
                'sales'
            )
        ];
    }
}
