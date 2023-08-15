<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckoutAdminPanel\Model\Reporting;

class ReportData
{
    /**
     * @var string
     */
    private string $section;

    /**
     * @var array
     */
    private array $content;

    /**
     * @param string $section
     * @param array $content
     */
    public function __construct(string $section, array $content)
    {
        $this->section = $section;
        $this->content = $content;
    }

    /**
     * Returns the name of the section
     *
     * @return string
     */
    public function getSection(): string
    {
        return $this->section;
    }

    /**
     * Returns the content of the section
     *
     * @return array
     */
    public function getContent(): array
    {
        return $this->content;
    }
}
