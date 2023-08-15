<?php
namespace Magento\AdvancedCheckout\Model\Cart;

/**
 * Proxy class for @see \Magento\AdvancedCheckout\Model\Cart
 */
class Proxy extends \Magento\AdvancedCheckout\Model\Cart implements \Magento\Framework\ObjectManager\NoninterceptableInterface
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;

    /**
     * Proxied instance name
     *
     * @var string
     */
    protected $_instanceName = null;

    /**
     * Proxied instance
     *
     * @var \Magento\AdvancedCheckout\Model\Cart
     */
    protected $_subject = null;

    /**
     * Instance shareability flag
     *
     * @var bool
     */
    protected $_isShared = null;

    /**
     * Proxy constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     * @param bool $shared
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, $instanceName = '\\Magento\\AdvancedCheckout\\Model\\Cart', $shared = true)
    {
        $this->_objectManager = $objectManager;
        $this->_instanceName = $instanceName;
        $this->_isShared = $shared;
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return ['_subject', '_isShared', '_instanceName'];
    }

    /**
     * Retrieve ObjectManager from global scope
     */
    public function __wakeup()
    {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    }

    /**
     * Clone proxied instance
     */
    public function __clone()
    {
        $this->_subject = clone $this->_getSubject();
    }

    /**
     * Get proxied instance
     *
     * @return \Magento\AdvancedCheckout\Model\Cart
     */
    protected function _getSubject()
    {
        if (!$this->_subject) {
            $this->_subject = true === $this->_isShared
                ? $this->_objectManager->get($this->_instanceName)
                : $this->_objectManager->create($this->_instanceName);
        }
        return $this->_subject;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext($context)
    {
        return $this->_getSubject()->setContext($context);
    }

    /**
     * {@inheritdoc}
     */
    public function setCustomer($customer)
    {
        return $this->_getSubject()->setCustomer($customer);
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomer()
    {
        return $this->_getSubject()->getCustomer();
    }

    /**
     * {@inheritdoc}
     */
    public function getStore()
    {
        return $this->_getSubject()->getStore();
    }

    /**
     * {@inheritdoc}
     */
    public function getQuote()
    {
        return $this->_getSubject()->getQuote();
    }

    /**
     * {@inheritdoc}
     */
    public function setQuote(\Magento\Quote\Model\Quote $quote)
    {
        return $this->_getSubject()->setQuote($quote);
    }

    /**
     * {@inheritdoc}
     */
    public function getActualQuote()
    {
        return $this->_getSubject()->getActualQuote();
    }

    /**
     * {@inheritdoc}
     */
    public function getQuoteSharedStoreIds()
    {
        return $this->_getSubject()->getQuoteSharedStoreIds();
    }

    /**
     * {@inheritdoc}
     */
    public function createQuote()
    {
        return $this->_getSubject()->createQuote();
    }

    /**
     * {@inheritdoc}
     */
    public function saveQuote($recollect = true)
    {
        return $this->_getSubject()->saveQuote($recollect);
    }

    /**
     * {@inheritdoc}
     */
    public function getPreferredStoreId()
    {
        return $this->_getSubject()->getPreferredStoreId();
    }

    /**
     * {@inheritdoc}
     */
    public function addProduct($product, $config = 1)
    {
        return $this->_getSubject()->addProduct($product, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function reorderItem(\Magento\Sales\Model\Order\Item $orderItem, $qty = 1)
    {
        return $this->_getSubject()->reorderItem($orderItem, $qty);
    }

    /**
     * {@inheritdoc}
     */
    public function addProducts(array $products)
    {
        return $this->_getSubject()->addProducts($products);
    }

    /**
     * {@inheritdoc}
     */
    public function updateQuoteItems($data)
    {
        return $this->_getSubject()->updateQuoteItems($data);
    }

    /**
     * {@inheritdoc}
     */
    public function moveQuoteItem($item, $moveTo)
    {
        return $this->_getSubject()->moveQuoteItem($item, $moveTo);
    }

    /**
     * {@inheritdoc}
     */
    public function copyQuote(\Magento\Quote\Model\Quote $quote, $active = false)
    {
        return $this->_getSubject()->copyQuote($quote, $active);
    }

    /**
     * {@inheritdoc}
     */
    public function updateFailedItems($failedItems, $cartItems)
    {
        return $this->_getSubject()->updateFailedItems($failedItems, $cartItems);
    }

    /**
     * {@inheritdoc}
     */
    public function prepareAddProductBySku($sku, $qty, $config = [])
    {
        return $this->_getSubject()->prepareAddProductBySku($sku, $qty, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function prepareAddProductsBySku(array $items)
    {
        return $this->_getSubject()->prepareAddProductsBySku($items);
    }

    /**
     * {@inheritdoc}
     */
    public function getQtyStatus(\Magento\Catalog\Model\Product $product, $requestedQty)
    {
        return $this->_getSubject()->getQtyStatus($product, $requestedQty);
    }

    /**
     * {@inheritdoc}
     */
    public function checkItems(array $items) : array
    {
        return $this->_getSubject()->checkItems($items);
    }

    /**
     * {@inheritdoc}
     */
    public function checkItem($sku, $qty, $config = [])
    {
        return $this->_getSubject()->checkItem($sku, $qty, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function setAffectedItemConfig($sku, $config)
    {
        return $this->_getSubject()->setAffectedItemConfig($sku, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function getAffectedItemConfig($sku)
    {
        return $this->_getSubject()->getAffectedItemConfig($sku);
    }

    /**
     * {@inheritdoc}
     */
    public function saveAffectedProducts(?\Magento\Checkout\Model\Cart\CartInterface $cart = null, $saveQuote = true)
    {
        return $this->_getSubject()->saveAffectedProducts($cart, $saveQuote);
    }

    /**
     * {@inheritdoc}
     */
    public function getAffectedItems($storeId = null)
    {
        return $this->_getSubject()->getAffectedItems($storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getSuccessfulAffectedItems()
    {
        return $this->_getSubject()->getSuccessfulAffectedItems();
    }

    /**
     * {@inheritdoc}
     */
    public function setAffectedItems($items, $storeId = null)
    {
        return $this->_getSubject()->setAffectedItems($items, $storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessages()
    {
        return $this->_getSubject()->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function getFailedItems()
    {
        return $this->_getSubject()->getFailedItems();
    }

    /**
     * {@inheritdoc}
     */
    public function updateItemQty($sku, $qty)
    {
        return $this->_getSubject()->updateItemQty($sku, $qty);
    }

    /**
     * {@inheritdoc}
     */
    public function removeAffectedItem($sku)
    {
        return $this->_getSubject()->removeAffectedItem($sku);
    }

    /**
     * {@inheritdoc}
     */
    public function removeAllAffectedItems()
    {
        return $this->_getSubject()->removeAllAffectedItems();
    }

    /**
     * {@inheritdoc}
     */
    public function removeSuccessItems()
    {
        return $this->_getSubject()->removeSuccessItems();
    }

    /**
     * {@inheritdoc}
     */
    public function setSession(\Magento\Framework\Session\SessionManagerInterface $session)
    {
        return $this->_getSubject()->setSession($session);
    }

    /**
     * {@inheritdoc}
     */
    public function getSession()
    {
        return $this->_getSubject()->getSession();
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentStore()
    {
        return $this->_getSubject()->getCurrentStore();
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrentStore($store)
    {
        return $this->_getSubject()->setCurrentStore($store);
    }

    /**
     * {@inheritdoc}
     */
    public function addData(array $arr)
    {
        return $this->_getSubject()->addData($arr);
    }

    /**
     * {@inheritdoc}
     */
    public function setData($key, $value = null)
    {
        return $this->_getSubject()->setData($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function unsetData($key = null)
    {
        return $this->_getSubject()->unsetData($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getData($key = '', $index = null)
    {
        return $this->_getSubject()->getData($key, $index);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataByPath($path)
    {
        return $this->_getSubject()->getDataByPath($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataByKey($key)
    {
        return $this->_getSubject()->getDataByKey($key);
    }

    /**
     * {@inheritdoc}
     */
    public function setDataUsingMethod($key, $args = [])
    {
        return $this->_getSubject()->setDataUsingMethod($key, $args);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataUsingMethod($key, $args = null)
    {
        return $this->_getSubject()->getDataUsingMethod($key, $args);
    }

    /**
     * {@inheritdoc}
     */
    public function hasData($key = '')
    {
        return $this->_getSubject()->hasData($key);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(array $keys = [])
    {
        return $this->_getSubject()->toArray($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToArray(array $keys = [])
    {
        return $this->_getSubject()->convertToArray($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function toXml(array $keys = [], $rootName = 'item', $addOpenTag = false, $addCdata = true)
    {
        return $this->_getSubject()->toXml($keys, $rootName, $addOpenTag, $addCdata);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToXml(array $arrAttributes = [], $rootName = 'item', $addOpenTag = false, $addCdata = true)
    {
        return $this->_getSubject()->convertToXml($arrAttributes, $rootName, $addOpenTag, $addCdata);
    }

    /**
     * {@inheritdoc}
     */
    public function toJson(array $keys = [])
    {
        return $this->_getSubject()->toJson($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToJson(array $keys = [])
    {
        return $this->_getSubject()->convertToJson($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function toString($format = '')
    {
        return $this->_getSubject()->toString($format);
    }

    /**
     * {@inheritdoc}
     */
    public function __call($method, $args)
    {
        return $this->_getSubject()->__call($method, $args);
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return $this->_getSubject()->isEmpty();
    }

    /**
     * {@inheritdoc}
     */
    public function serialize($keys = [], $valueSeparator = '=', $fieldSeparator = ' ', $quote = '"')
    {
        return $this->_getSubject()->serialize($keys, $valueSeparator, $fieldSeparator, $quote);
    }

    /**
     * {@inheritdoc}
     */
    public function debug($data = null, &$objects = [])
    {
        return $this->_getSubject()->debug($data, $objects);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        return $this->_getSubject()->offsetSet($offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return $this->_getSubject()->offsetExists($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        return $this->_getSubject()->offsetUnset($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->_getSubject()->offsetGet($offset);
    }
}
