<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Block\Adminhtml\Banner\Edit;

use Magento\Banner\Model\BannerFactory;
use Magento\Banner\Model\ResourceModel\Banner;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\RequestInterface;

/**
 * Class for common code for buttons on the create/edit banner form
 */
class GenericButton
{
    /**
     * @var BannerFactory
     */
    private $bannerFactory;

    /*
     * @var Banner
     */
    private $bannerResourceModel;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param UrlInterface $urlBuilder
     * @param RequestInterface $request
     * @param BannerFactory $bannerFactory
     * @param Banner $bannerResourceModel
     */
    public function __construct(
        UrlInterface $urlBuilder,
        RequestInterface $request,
        BannerFactory $bannerFactory,
        Banner $bannerResourceModel
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
        $this->bannerFactory = $bannerFactory;
        $this->bannerResourceModel = $bannerResourceModel;
    }

    /**
     * Return banner id
     *
     * @return int|null
     */
    public function getBannerId()
    {
        $banner = $this->bannerFactory->create();

        $this->bannerResourceModel->load(
            $banner,
            $this->request->getParam('id')
        );

        return $banner->getBannerId() ?: null;
    }

    /**
     * Generate url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->urlBuilder->getUrl($route, $params);
    }
}
