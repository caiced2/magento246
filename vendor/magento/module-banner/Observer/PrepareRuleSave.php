<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Banner\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Backend\Helper\Js;

/**
 * Class preparing rule saving for banners
 */
class PrepareRuleSave implements ObserverInterface
{
    /**
     * Adminhtml js
     *
     * @var Js
     */
    protected $_adminhtmlJs = null;

    /**
     * @param Js $adminhtmlJs
     */
    public function __construct(Js $adminhtmlJs)
    {
        $this->_adminhtmlJs = $adminhtmlJs;
    }

    /**
     * Prepare sales rule post data to save
     *
     * @param Observer $observer
     *
     * @return $this
     */
    public function execute(Observer $observer): self
    {
        $request = $observer->getEvent()->getRequest();
        $relatedBanners = $request->getPost('related_banners');
        if (isset($relatedBanners)) {
            $banners = is_array($relatedBanners)
                ? $relatedBanners : $this->_adminhtmlJs->decodeGridSerializedInput($relatedBanners);
            $request->setPostValue('related_banners', $banners);
        }

        return $this;
    }
}
