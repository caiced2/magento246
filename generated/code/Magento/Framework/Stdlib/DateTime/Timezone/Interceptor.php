<?php
namespace Magento\Framework\Stdlib\DateTime\Timezone;

/**
 * Interceptor class for @see \Magento\Framework\Stdlib\DateTime\Timezone
 */
class Interceptor extends \Magento\Framework\Stdlib\DateTime\Timezone implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\ScopeResolverInterface $scopeResolver, \Magento\Framework\Locale\ResolverInterface $localeResolver, \Magento\Framework\Stdlib\DateTime $dateTime, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, $scopeType, $defaultTimezonePath, \Magento\Framework\Stdlib\DateTime\Intl\DateFormatterFactory $dateFormatterFactory)
    {
        $this->___init();
        parent::__construct($scopeResolver, $localeResolver, $dateTime, $scopeConfig, $scopeType, $defaultTimezonePath, $dateFormatterFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultTimezonePath()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getDefaultTimezonePath');
        return $pluginInfo ? $this->___callPlugins('getDefaultTimezonePath', func_get_args(), $pluginInfo) : parent::getDefaultTimezonePath();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultTimezone()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getDefaultTimezone');
        return $pluginInfo ? $this->___callPlugins('getDefaultTimezone', func_get_args(), $pluginInfo) : parent::getDefaultTimezone();
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTimezone($scopeType = null, $scopeCode = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getConfigTimezone');
        return $pluginInfo ? $this->___callPlugins('getConfigTimezone', func_get_args(), $pluginInfo) : parent::getConfigTimezone($scopeType, $scopeCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getDateFormat($type = 3)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getDateFormat');
        return $pluginInfo ? $this->___callPlugins('getDateFormat', func_get_args(), $pluginInfo) : parent::getDateFormat($type);
    }

    /**
     * {@inheritdoc}
     */
    public function getDateFormatWithLongYear()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getDateFormatWithLongYear');
        return $pluginInfo ? $this->___callPlugins('getDateFormatWithLongYear', func_get_args(), $pluginInfo) : parent::getDateFormatWithLongYear();
    }

    /**
     * {@inheritdoc}
     */
    public function getTimeFormat($type = 3)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getTimeFormat');
        return $pluginInfo ? $this->___callPlugins('getTimeFormat', func_get_args(), $pluginInfo) : parent::getTimeFormat($type);
    }

    /**
     * {@inheritdoc}
     */
    public function getDateTimeFormat($type)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getDateTimeFormat');
        return $pluginInfo ? $this->___callPlugins('getDateTimeFormat', func_get_args(), $pluginInfo) : parent::getDateTimeFormat($type);
    }

    /**
     * {@inheritdoc}
     */
    public function date($date = null, $locale = null, $useTimezone = true, $includeTime = true)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'date');
        return $pluginInfo ? $this->___callPlugins('date', func_get_args(), $pluginInfo) : parent::date($date, $locale, $useTimezone, $includeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function scopeDate($scope = null, $date = null, $includeTime = false)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'scopeDate');
        return $pluginInfo ? $this->___callPlugins('scopeDate', func_get_args(), $pluginInfo) : parent::scopeDate($scope, $date, $includeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function formatDate($date = null, $format = 3, $showTime = false)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'formatDate');
        return $pluginInfo ? $this->___callPlugins('formatDate', func_get_args(), $pluginInfo) : parent::formatDate($date, $format, $showTime);
    }

    /**
     * {@inheritdoc}
     */
    public function scopeTimeStamp($scope = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'scopeTimeStamp');
        return $pluginInfo ? $this->___callPlugins('scopeTimeStamp', func_get_args(), $pluginInfo) : parent::scopeTimeStamp($scope);
    }

    /**
     * {@inheritdoc}
     */
    public function isScopeDateInInterval($scope, $dateFrom = null, $dateTo = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isScopeDateInInterval');
        return $pluginInfo ? $this->___callPlugins('isScopeDateInInterval', func_get_args(), $pluginInfo) : parent::isScopeDateInInterval($scope, $dateFrom, $dateTo);
    }

    /**
     * {@inheritdoc}
     */
    public function formatDateTime($date, $dateType = 3, $timeType = 3, $locale = null, $timezone = null, $pattern = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'formatDateTime');
        return $pluginInfo ? $this->___callPlugins('formatDateTime', func_get_args(), $pluginInfo) : parent::formatDateTime($date, $dateType, $timeType, $locale, $timezone, $pattern);
    }

    /**
     * {@inheritdoc}
     */
    public function convertConfigTimeToUtc($date, $format = 'Y-m-d H:i:s')
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'convertConfigTimeToUtc');
        return $pluginInfo ? $this->___callPlugins('convertConfigTimeToUtc', func_get_args(), $pluginInfo) : parent::convertConfigTimeToUtc($date, $format);
    }
}
