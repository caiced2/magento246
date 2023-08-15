<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\QuickCheckout\Setup;

use Magento\QuickCheckout\Helper\Data;

class MetadataData
{
    private const INSTALLATION_DATE_PATH = 'payment/quick_checkout/installation_date';

    /**
     * @var Data
     */
    private $dataHelper;

    /**
     * @param Data $dataHelper
     */
    public function __construct(Data $dataHelper)
    {
        $this->dataHelper = $dataHelper;
    }

    /**
     * Save installation data in database
     *
     * @param int $installationDate
     * @return void
     */
    public function saveInstallationDate(int $installationDate) : void
    {
        $this->dataHelper->setData(self::INSTALLATION_DATE_PATH, (string) $installationDate);
    }

    /**
     * Retrieve installation date from database
     *
     * @return int
     */
    public function getInstallationDate() : int
    {
        return (int) $this->dataHelper->getData(self::INSTALLATION_DATE_PATH) ?? 0;
    }

    /**
     * Delete installation date in database
     *
     * @return void
     */
    public function clearInstallationDate() : void
    {
        $this->dataHelper->deleteData(self::INSTALLATION_DATE_PATH);
    }
}
