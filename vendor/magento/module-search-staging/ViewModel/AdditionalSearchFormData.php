<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SearchStaging\ViewModel;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Staging\Model\VersionManager;

/**
 * View model for additional params for search
 */
class AdditionalSearchFormData implements ArgumentInterface
{

    /**
     * @var \Magento\Staging\Model\VersionManager
     */
    private $versionManager;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param RequestInterface $request
     * @param VersionManager $versionManager
     */
    public function __construct(
        RequestInterface $request,
        VersionManager $versionManager
    ) {
        $this->request = $request;
        $this->versionManager = $versionManager;
    }

    /**
     * Return additional params for search staging
     *
     * @return array
     */
    public function getFormData(): array
    {
        $queryParams = [];
        if ($this->versionManager->isPreviewVersion()) {
            $queryPreviewParams = $this->request->getParams();
            $queryParams = [
                ['name' => '___version', 'value'=> $queryPreviewParams['___version']],
                ['name'=> '__signature', 'value'=> $queryPreviewParams['__signature']],
                ['name'=> '__timestamp', 'value'=> $queryPreviewParams['__timestamp']],
            ];
        }
        return $queryParams;
    }
}
