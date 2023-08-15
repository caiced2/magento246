<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Plugin;

use Magento\Customer\Api\Data\CustomerInterface;

/**
 * Plugin for retrieving prefix/suffix options depending on attribute 'required' status
 */
class FormatPrefixSuffixOptions
{
    /**
     * @var \Magento\CustomerCustomAttributes\Model\Options
     */
    private $options;

    /**
     * @param \Magento\CustomerCustomAttributes\Model\Options $options
     */
    public function __construct(
        \Magento\CustomerCustomAttributes\Model\Options $options
    ) {
        $this->options = $options;
    }

    /**
     * Retrieve name prefix options for current entity type
     *
     * @param \Magento\Customer\Block\Widget\Name $subject
     * @param callable $proceed
     *
     * @return array|bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetPrefixOptions(
        \Magento\Customer\Block\Widget\Name $subject,
        callable $proceed
    ) {
        $entityType = $this->getCurrentEntityType($subject);
        $prefixOptions = $this->options->getNamePrefixOptions(null, $entityType);

        if ($subject->getObject() && !empty($prefixOptions)) {
            $prefixOption = $subject->getObject()->getPrefix();
            if ($prefixOption) {
                $oldPrefix = $subject->escapeHtml(trim($prefixOption));
                if (!isset($prefixOptions[$oldPrefix]) && !isset($prefixOptions[$prefixOption])) {
                    $prefixOptions[$oldPrefix] = $oldPrefix;
                }
            }
        }

        return $prefixOptions;
    }

    /**
     * Retrieve name suffix options for current entity type
     *
     * @param \Magento\Customer\Block\Widget\Name $subject
     * @param callable $proceed
     *
     * @return array|bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetSuffixOptions(
        \Magento\Customer\Block\Widget\Name $subject,
        callable $proceed
    ) {
        $entityType = $this->getCurrentEntityType($subject);
        $suffixOptions = $this->options->getNameSuffixOptions(null, $entityType);

        if ($subject->getObject() && !empty($suffixOptions)) {
            $suffixOption = $subject->getObject()->getSuffix();
            if ($suffixOption) {
                $oldSuffix = $subject->escapeHtml(trim($suffixOption));
                if (!isset($suffixOptions[$oldSuffix]) && !isset($suffixOptions[$suffixOption])) {
                    $suffixOptions[$oldSuffix] = $oldSuffix;
                }
            }
        }

        return $suffixOptions;
    }

    /**
     * Get current block entity type
     *
     * @param \Magento\Customer\Block\Widget\Name $subject
     *
     * @return string
     */
    private function getCurrentEntityType($subject)
    {
        return ($subject->getForceUseCustomerAttributes()
            || $subject->getObject() instanceof CustomerInterface)
            ? 'customer'
            : 'customer_address';
    }
}
