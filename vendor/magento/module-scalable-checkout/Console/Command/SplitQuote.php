<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\ScalableCheckout\Console\Command;

use Symfony\Component\Console\Input\InputOption;
use Magento\Framework\ForeignKey\Migration\AbstractCommand;

/**
 * @codeCoverageIgnore
 * @deprecated split database solution is deprecated and will be removed
 */
class SplitQuote extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function getCommandName()
    {
        return 'setup:db-schema:split-quote';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandDescription()
    {
        return 'Move checkout quote related tables to a separate DB server. Deprecated since 2.4.2 and will be removed';
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
                'Checkout DB Server host'
            ),
            new InputOption(
                self::DB_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'Checkout Database Name'
            ),
            new InputOption(
                self::USER_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'Checkout DB user name'
            ),
            new InputOption(
                self::PASSWORD,
                null,
                InputOption::VALUE_OPTIONAL,
                'Checkout DB user password'
            ),
            new InputOption(
                self::CONNECTION,
                null,
                InputOption::VALUE_OPTIONAL,
                'Checkout connection name',
                'checkout'
            ),
            new InputOption(
                self::RESOURCE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Checkout resource name',
                'checkout'
            )
        ];
    }
}
