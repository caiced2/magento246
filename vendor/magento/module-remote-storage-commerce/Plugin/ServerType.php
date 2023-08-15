<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RemoteStorageCommerce\Plugin;

use Magento\RemoteStorage\Model\Config;
use Magento\ScheduledImportExport\Model\Scheduled\Operation\Data;

/**
 * Modifies server type.
 */
class ServerType
{
    /**
     * @var bool
     */
    private $isEnabled;

    /**
     * @param Config $config
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\RuntimeException
     */
    public function __construct(Config $config)
    {
        $this->isEnabled = $config->isEnabled();
    }

    /**
     * Modifies server type if remote storage is enabled.
     *
     * @param Data $subject
     * @param array $result
     * @return array
     */
    public function afterGetServerTypesOptionArray(Data $subject, array $result): array
    {
        if ($this->isEnabled) {
            return [
                Data::FILE_STORAGE => __('Remote Storage'),
                Data::FTP_STORAGE => __('Remote FTP')
            ];
        }

        return $result;
    }
}
