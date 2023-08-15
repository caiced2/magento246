<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Block\Customer;

use Magento\Customer\Block\Account\SortLinkInterface;

/**
 * "Reward Points" link
 *
 * @api
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 * @since 100.0.2
 */
class AccountLink extends \Magento\Framework\View\Element\Html\Link\Current implements SortLinkInterface
{
    /**
     * @var \Magento\Reward\Helper\Data
     */
    protected $_rewardHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\DefaultPathInterface $defaultPath
     * @param \Magento\Reward\Helper\Data $rewardHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\DefaultPathInterface $defaultPath,
        \Magento\Reward\Helper\Data $rewardHelper,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath, $data);
        $this->_rewardHelper = $rewardHelper;
    }

    /**
     * Render block HTML
     *
     * @inheritdoc
     * @return string
     */
    protected function _toHtml()
    {
        return $this->_rewardHelper->isEnabledOnFront() ? parent::_toHtml() : '';
    }

    /**
     * {@inheritdoc}
     * @since 101.0.0
     */
    public function getSortOrder()
    {
        return $this->getData(self::SORT_ORDER);
    }
}
