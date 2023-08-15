<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\QuickCheckout\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class Data
{
    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var ScopeConfigInterface
     */
    private $configScope;

    /**
     * @param WriterInterface $configWriter
     * @param ScopeConfigInterface $configScope
     */
    public function __construct(WriterInterface $configWriter, ScopeConfigInterface $configScope)
    {
        $this->configWriter = $configWriter;
        $this->configScope = $configScope;
    }

    /**
     * Save config data to database
     *
     * @param string $path
     * @param string $value
     */
    public function setData(string $path, string $value) : void
    {
        $this->configWriter->save($path, $value);
    }

    /**
     * Retrieve config data from database
     *
     * @param string $path
     * @return mixed
     */
    public function getData(string $path)
    {
        return $this->configScope->getValue($path);
    }

    /**
     * Delete config data in database
     *
     * @param string $path
     * @return void
     */
    public function deleteData(string $path)
    {
        $this->configWriter->delete($path);
    }
}
