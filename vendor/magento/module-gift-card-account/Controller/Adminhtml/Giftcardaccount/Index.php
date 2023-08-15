<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCardAccount\Controller\Adminhtml\Giftcardaccount;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Escaper;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Backend\Model\UrlInterface;

class Index extends \Magento\GiftCardAccount\Controller\Adminhtml\Giftcardaccount implements HttpGetActionInterface
{
    /**
     * Defines if status message of code pool is show
     *
     * @var bool
     */
    protected $_showCodePoolStatusMessage = true;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @inheritDoc
     *
     * @param Json|null $json
     * @param UrlInterface|null $url
     * @param Escaper|null $escaper
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter,
        ?Json $json = null,
        ?UrlInterface $url = null,
        ?Escaper $escaper = null
    ) {
        parent::__construct($context, $coreRegistry, $fileFactory, $dateFilter);
        $this->json = $json ?? ObjectManager::getInstance()->get(Json::class);
        $this->url = $url ?? ObjectManager::getInstance()->get(UrlInterface::class);
        $this->escaper = $escaper ?? ObjectManager::getInstance()->get(Escaper::class);
    }

    /**
     * Default action
     *
     * @return void
     */
    public function execute()
    {
        if ($this->_showCodePoolStatusMessage) {
            $usage = $this->_objectManager->create(\Magento\GiftCardAccount\Model\Pool::class)->getPoolUsageInfo();
            $url = $this->json->serialize([
                'action' => $this->url->getUrl('adminhtml/*/generate'),
                'data' => (object)[]
            ]);
            $notice = __(
                'Code Pool used: <b>%1%</b> (free <b>%2</b> of <b>%3</b> total). '
                .'Generate new code pool <a href="#" data-post="%4">here</a>.',
                round($usage->getPercent(), 2),
                $usage->getFree(),
                $usage->getTotal(),
                $this->escaper->escapeHtmlAttr($url)
            );
            if ($usage->getPercent() == 100) {
                $this->messageManager->addError($notice);
            } else {
                $this->messageManager->addNotice($notice);
            }
        }

        $this->_view->loadLayout();
        $this->_setActiveMenu('Magento_GiftCardAccount::customer_giftcardaccount');
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Gift Card Accounts'));
        $this->_view->renderLayout();
    }

    /**
     * Setter for code pool status message flag
     *
     * @param bool $isShow
     * @return void
     */
    public function setShowCodePoolStatusMessage($isShow)
    {
        $this->_showCodePoolStatusMessage = (bool)$isShow;
    }
}
