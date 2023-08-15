<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRuleSampleData\Setup;

use Magento\Framework\Setup;

class Installer implements Setup\SampleData\InstallerInterface
{
    /**
     * Model class for products
     *
     * @var \Magento\TargetRuleSampleData\Model\Rule
     */
    protected $rule;

    /**
     * Constructor
     *
     * @param \Magento\TargetRuleSampleData\Model\Rule $rule
     */
    public function __construct(
        \Magento\TargetRuleSampleData\Model\Rule $rule

    ) {
        $this->rule = $rule;
    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {
        $this->rule->install(
            [
                \Magento\TargetRule\Model\Rule::RELATED_PRODUCTS => 'Magento_TargetRuleSampleData::fixtures/crossell.csv',
                \Magento\TargetRule\Model\Rule::UP_SELLS => 'Magento_TargetRuleSampleData::fixtures/related.csv',
                \Magento\TargetRule\Model\Rule::CROSS_SELLS => 'Magento_TargetRuleSampleData::fixtures/upsell.csv'
            ]
        );
    }
}
