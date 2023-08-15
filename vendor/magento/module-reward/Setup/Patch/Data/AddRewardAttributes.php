<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reward\Setup\Patch\Data;

use Magento\Cms\Model\PageFactory;
use Magento\Quote\Setup\QuoteSetupFactory;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class AddRewardAttributes implements
    DataPatchInterface,
    PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * @var SalesSetupFactory
     */
    private $salesSetupFactory;

    /**
     * @var QuoteSetupFactory
     */
    private $quoteSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param PageFactory $pageFactory
     * @param SalesSetupFactory $salesSetupFactory
     * @param QuoteSetupFactory $quoteSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        PageFactory $pageFactory,
        SalesSetupFactory $salesSetupFactory,
        QuoteSetupFactory $quoteSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->pageFactory = $pageFactory;
        $this->salesSetupFactory = $salesSetupFactory;
        $this->quoteSetupFactory = $quoteSetupFactory;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function apply()
    {
        $quoteInstaller = $this->quoteSetupFactory->create(
            ['resourceName' => 'quote_setup', 'setup' => $this->moduleDataSetup]
        );
        $salesInstaller = $this->salesSetupFactory->create(
            ['resourceName' => 'sales_setup', 'setup' => $this->moduleDataSetup]
        );

        $quoteInstaller->addAttribute(
            'quote',
            'use_reward_points',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER]
        );
        $quoteInstaller->addAttribute(
            'quote',
            'reward_points_balance',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER]
        );
        $quoteInstaller->addAttribute(
            'quote',
            'base_reward_currency_amount',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
        );
        $quoteInstaller->addAttribute(
            'quote',
            'reward_currency_amount',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
        );

        $quoteInstaller->addAttribute(
            'quote_address',
            'reward_points_balance',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER]
        );
        $quoteInstaller->addAttribute(
            'quote_address',
            'base_reward_currency_amount',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
        );
        $quoteInstaller->addAttribute(
            'quote_address',
            'reward_currency_amount',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
        );

        $salesInstaller->addAttribute(
            'order',
            'reward_points_balance',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER]
        );
        $salesInstaller->addAttribute(
            'order',
            'base_reward_currency_amount',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
        );
        $salesInstaller->addAttribute(
            'order',
            'reward_currency_amount',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
        );
        $salesInstaller->addAttribute(
            'order',
            'base_rwrd_crrncy_amt_invoiced',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
        );
        $salesInstaller->addAttribute(
            'order',
            'rwrd_currency_amount_invoiced',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
        );
        $salesInstaller->addAttribute(
            'order',
            'base_rwrd_crrncy_amnt_refnded',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
        );
        $salesInstaller->addAttribute(
            'order',
            'rwrd_crrncy_amnt_refunded',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
        );

        $salesInstaller->addAttribute(
            'invoice',
            'base_reward_currency_amount',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
        );
        $salesInstaller->addAttribute(
            'invoice',
            'reward_currency_amount',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
        );

        $salesInstaller->addAttribute(
            'creditmemo',
            'base_reward_currency_amount',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
        );
        $salesInstaller->addAttribute(
            'creditmemo',
            'reward_currency_amount',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
        );

        $salesInstaller->addAttribute(
            'invoice',
            'reward_points_balance',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER]
        );

        $salesInstaller->addAttribute(
            'creditmemo',
            'reward_points_balance',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER]
        );

        $salesInstaller->addAttribute(
            'order',
            'reward_points_balance_refund',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER]
        );
        $salesInstaller->addAttribute(
            'creditmemo',
            'reward_points_balance_refund',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER]
        );

        $quoteInstaller->addAttribute(
            'quote',
            'base_reward_currency_amount',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
        );
        $quoteInstaller->addAttribute(
            'quote',
            'reward_currency_amount',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
        );

        $quoteInstaller->addAttribute(
            'order',
            'reward_points_balance_refunded',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER]
        );

        $quoteInstaller->addAttribute(
            'order',
            'reward_salesrule_points',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER]
        );

        $salesInstaller->addAttribute(
            'customer',
            'reward_update_notification',
            [
                'type' => 'int',
                'visible' => 0,
                'required' => false,
                'visible_on_front' => 1,
                'is_user_defined' => 0,
                'is_system' => 1,
                'is_hidden' => 1,
                'label' => 'Reward update notification'
            ]
        );

        $salesInstaller->addAttribute(
            'customer',
            'reward_warning_notification',
            [
                'type' => 'int',
                'visible' => 0,
                'required' => false,
                'visible_on_front' => 1,
                'is_user_defined' => 0,
                'is_system' => 1,
                'is_hidden' => 1,
                'label' => 'Reward warning notification'
            ]
        );

        // @codingStandardsIgnoreStart
        $cmsPage = [
            'title' => 'Reward Points',
            'page_layout' => '1column',
            'identifier' => 'reward-points',
            'content_heading' => 'Reward Points',
            'is_active' => 1,
            'stores' => [0],
            'content' => '<p>The Reward Points Program allows you to earn points for certain actions you take on the site. Points are awarded based on making purchases and customer actions such as submitting reviews.</p>

        <h2>Benefits of Reward Points for Registered Customers</h2>
        <p>Once you register you will be able to earn and accrue reward points, which are then redeemable at time of purchase towards the cost of your order. Rewards are an added bonus to your shopping experience on the site and just one of the ways we thank you for being a loyal customer.</p>

        <h2>Earning Reward Points</h2>
        <p>Rewards can currently be earned for the following actions:</p>
        <ul>
        <li>Making purchases — every time you make a purchase you earn points based on the price of products purchased and these points are added to your Reward Points balance.</li>
        <li>Registering on the site.</li>
        <li>Subscribing to a newsletter for the first time.</li>
        <li>Sending Invitations — Earn points by inviting your friends to join the site.</li>
        <li>Converting Invitations to Customer — Earn points for every invitation you send out which leads to your friends registering on the site.</li>
        <li>Converting Invitations to Order — Earn points for every invitation you send out which leads to a sale.</li>
        <li>Review Submission — Earn points for submitting product reviews.</li>
        </ul>

        <h2>Reward Points Exchange Rates</h2>
        <p>The value of reward points is determined by an exchange rate of both currency spent on products to points, and an exchange rate of points earned to currency for spending on future purchases.</p>

        <h2>Redeeming Reward Points</h2>
        <p>You can redeem your reward points at checkout. If you have accumulated enough points to redeem them you will have the option of using points as one of the payment methods.  The option to use reward points, as well as your balance and the monetary equivalent this balance, will be shown to you in the Payment Method area of the checkout.  Redeemable reward points can be used in conjunction with other payment methods such as credit cards, gift cards and more.</p>
        <p><img src="{{view url="Magento_Reward::images/payment.png"}}" alt="Payment Information" /></p>

        <h2>Reward Points Minimums and Maximums</h2>
        <p>Reward points may be capped at a minimum value required for redemption.  If this option is selected you will not be able to use your reward points until you accrue a minimum number of points, at which point they will become available for redemption.</p>
        <p>Reward points may also be capped at the maximum value of points which can be accrued. If this option is selected you will need to redeem your accrued points before you are able to earn more points.</p>

        <h2>Managing My Reward Points</h2>
        <p>You have the ability to view and manage your points through your <a href="{{store url="customer/account"}}">Customer Account</a>. From your account you will be able to view your total points (and currency equivalent), minimum needed to redeem, whether you have reached the maximum points limit and a cumulative history of points acquired, redeemed and lost. The history record will retain and display historical rates and currency for informational purposes. The history will also show you comprehensive informational messages regarding points, including expiration notifications.</p>
        <p><img src="{{view url="Magento_Reward::images/my_account.png"}}" alt="My Account" /></p>

        <h2>Reward Points Expiration</h2>
        <p>Reward points can be set to expire. Points will expire in the order form which they were first earned.</p>
        <p><strong>Note</strong>: You can sign up to receive email notifications each time your balance changes when you either earn, redeem or lose points, as well as point expiration notifications. This option is found in the <a href="{{store url="reward/customer/info"}}">Reward Points section</a> of the My Account area.</p>
        ',
        ];
        // @codingStandardsIgnoreEnd

        $this->pageFactory->create()->setData($cmsPage)->save();
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [

        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '2.0.0';
    }
}
