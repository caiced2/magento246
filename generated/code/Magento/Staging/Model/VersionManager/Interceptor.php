<?php
namespace Magento\Staging\Model\VersionManager;

/**
 * Interceptor class for @see \Magento\Staging\Model\VersionManager
 */
class Interceptor extends \Magento\Staging\Model\VersionManager implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Staging\Model\UpdateFactory $updateFactory, \Magento\Staging\Api\UpdateRepositoryInterface $updateRepository, \Magento\Framework\App\RequestInterface $request, \Magento\Staging\Model\VersionHistoryInterface $versionHistory)
    {
        $this->___init();
        parent::__construct($updateFactory, $updateRepository, $request, $versionHistory);
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrentVersionId($versionId)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setCurrentVersionId');
        return $pluginInfo ? $this->___callPlugins('setCurrentVersionId', func_get_args(), $pluginInfo) : parent::setCurrentVersionId($versionId);
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getVersion');
        return $pluginInfo ? $this->___callPlugins('getVersion', func_get_args(), $pluginInfo) : parent::getVersion();
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestedTimestamp()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getRequestedTimestamp');
        return $pluginInfo ? $this->___callPlugins('getRequestedTimestamp', func_get_args(), $pluginInfo) : parent::getRequestedTimestamp();
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentVersion()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getCurrentVersion');
        return $pluginInfo ? $this->___callPlugins('getCurrentVersion', func_get_args(), $pluginInfo) : parent::getCurrentVersion();
    }

    /**
     * {@inheritdoc}
     */
    public function isPreviewVersion()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isPreviewVersion');
        return $pluginInfo ? $this->___callPlugins('isPreviewVersion', func_get_args(), $pluginInfo) : parent::isPreviewVersion();
    }
}
