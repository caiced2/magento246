<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\QuickCheckout\ViewModel;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\QuickCheckout\Setup\MetadataData;
use Magento\Store\Model\StoreManagerInterface;
use Magento\QuickCheckout\Model\Config;

/**
 * Gets user version and mode
 */
class Metadata implements ArgumentInterface
{
    /**
     * @var Session
     */
    private $authSession;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var File
     */
    private $driverFile;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var MetadataData
     */
    private $metadataData;

    /**
     * @param Session $authSession
     * @param Config $config
     * @param File $driverFile
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param MetadataData $metadataData
     */
    public function __construct(
        Session $authSession,
        Config $config,
        File $driverFile,
        Context $context,
        StoreManagerInterface $storeManager,
        MetadataData $metadataData
    ) {
        $this->authSession = $authSession;
        $this->config = $config;
        $this->driverFile = $driverFile;
        $this->context = $context;
        $this->storeManager = $storeManager;
        $this->metadataData = $metadataData;
    }

    /**
     * Get quick checkout product version
     *
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function getQuickCheckoutVersion() :string
    {
        $pathToModule = $this->getFullPathToModuleComposerFile();
        if (!$pathToModule) {
            return '';
        }
        $content = $this->driverFile->fileGetContents($pathToModule);
        if (!$content) {
            return '';
        }
        $jsonContent = json_decode($content, true);
        return !empty($jsonContent['version']) ? $jsonContent['version'] : '';
    }

    /**
     * Get quick checkout product mode
     *
     * @return string
     */
    private function getQuickCheckoutMode() :string
    {
        $mode = '';
        foreach ($this->storeManager->getWebsites() as $website) {
            $websiteMode = $this->getWebsiteMode($website->getCode());
            if ($websiteMode == 'Production_enabled') { //Highest value, no need to iterate on all
                return $websiteMode;
            }
            $mode = $this->getMaxMode($mode, $websiteMode);
        }
        return $mode;
    }

    /**
     * Get website quick checkout product mode
     *
     * @param string $code
     * @return string
     */
    private function getWebsiteMode(string $code) : string
    {
        $mode = $this->config->isProductionEnvironment($code) ? 'Production' : 'Sandbox';
        $enabled =
            $this->config->isEnabled($code)
            && !empty($this->config->getPublishableKey($code))
            && !empty($this->config->getApiKey($code))
                ? 'enabled'
                : 'disabled';
        return $mode . '_' . $enabled;
    }

    /**
     * Compare and return quick checkout maximum product mode
     *
     * @param string $lhs
     * @param string $rhs
     * @return string
     */
    private function getMaxMode(string $lhs, string $rhs) : string
    {
        if ($lhs === 'Production_enabled' || $rhs === 'Production_enabled') {
            return 'Production_enabled';
        }
        if ($lhs === 'Production_disabled' || $rhs === 'Production_disabled') {
            return 'Production_disabled';
        }
        if ($lhs === 'Sandbox_enabled' || $rhs === 'Sandbox_enabled') {
            return 'Sandbox_enabled';
        }
        if ($lhs === 'Sandbox_disabled' || $rhs === 'Sandbox_disabled') {
            return 'Sandbox_disabled';
        }
        return '';
    }

    /**
     * Get quick checkout installation date
     *
     * @return int
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function getQuickCheckoutInstallationDate() : int
    {
        return $this->metadataData->getInstallationDate();
    }

    /**
     * Get full path to module (composer.json file)
     *
     * @return string
     */
    private function getFullPathToModuleComposerFile() : string
    {
        $registrar = new ComponentRegistrar();
        $path = $registrar->getPath(ComponentRegistrar::MODULE, "Magento_QuickCheckout");
        return $path . "/composer.json";
    }

    /**
     * Get current user role name
     *
     * @return string
     */
    private function getCurrentUserRoleName() : string
    {
        return $this->authSession->getUser()->getRole()->getRoleName();
    }

    /**
     * Get account id
     *
     * @return string
     */
    private function getAccountId() : string
    {
        return explode(':', $this->context->getHttpHeader()->getHttpHost())[0] ?? '';
    }

    /**
     * Get user id
     *
     * @return string
     */
    public function getCurrentAdminUser() : string
    {
        return hash('sha256', 'ADMIN_USER' . $this->authSession->getUser()->getEmail());
    }

    /**
     * Get tracking user data
     *
     * @return array
     */
    public function getTrackingUserData() : array
    {
        return [
            "id" => $this->getCurrentAdminUser(),
            "adminUserRole" => $this->getCurrentUserRoleName(),
        ];
    }

    /**
     * Get tracking account data
     *
     * @return array
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getTrackingAccountData() : array
    {
        return [
            "id" => $this->getAccountId(),
            "quickCheckout_productVersion" => $this->getQuickCheckoutVersion(),
            "quickCheckout_productMode" => $this->getQuickCheckoutMode(),
            "quickCheckout_productInstallationDate" => $this->getQuickCheckoutInstallationDate()
        ];
    }

    /**
     * Get tracking data hash
     *
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getTrackingDataHash() : string
    {
        return hash('sha256', json_encode($this->getTrackingAccountData()));
    }
}
