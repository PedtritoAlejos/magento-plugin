<?php

namespace DUna\Payments\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\HTTP\Adapter\Curl;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\AuthenticationException;
use Zend_Http_Client;
use Magento\Framework\Serialize\Serializer\Json;
use DUna\Payments\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\Encryption\EncryptorInterface;

class OrderTokens
{

    CONST URL_PRODUCTION = 'https://apigw.getduna.com/merchants/orders';
    CONST URL_STAGING = 'https://staging-apigw.getduna.com/merchants/orders';
    CONST CONTENT_TYPE = 'application/json';
    const PRIVATE_KEY_PRODUCTION = 'private_key_production';
    const PRIVATE_KEY_STAGING = 'private_key_stage';

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var Category
     */
    private $category;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    public function __construct(
        Session $checkoutSession,
        Curl $curl,
        Json $json,
        Data $helper,
        StoreManagerInterface $storeManager,
        PriceCurrencyInterface $priceCurrency,
        Category $category,
        EncryptorInterface $encryptor
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->curl = $curl;
        $this->json = $json;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->priceCurrency = $priceCurrency;
        $this->category = $category;
        $this->encryptor = $encryptor;
    }

    /**
     * @return string
     */
    private function getUrl(): string
    {
        return self::URL_STAGING;
    }

    /**
     * @return string
     */
    public function getPrivateKey(): string
    {
        $env = $this->helper->getEnv();
        if ($env == 'production') {
            $privateKey = $this->helper->getGeneralConfig(self::PRIVATE_KEY_PRODUCTION);
        }
        if ($env == 'staging') {
            $privateKey = $this->helper->getGeneralConfig(self::PRIVATE_KEY_STAGING);
        }
        return $this->encryptor->decrypt($privateKey);
    }

    /**
     * @return string[]
     */
    private function getHeaders(): array
    {
        return [
            'X-Api-Key: ' . $this->getPrivateKey(),
            'Content-Type: ' . self::CONTENT_TYPE
        ];
    }

    /**
     * @param $body
     * @return mixed
     * @throws LocalizedException
     */
    private function request($body)
    {
        $method = Zend_Http_Client::POST;
        $url = $this->getUrl();
        $http_ver = '1.1';
        $headers = $this->getHeaders();

        $configuration['header'] = false;
        $this->curl->setConfig($configuration);

        $this->curl->write($method, $url, $http_ver, $headers, $body);

        $response = $this->curl->read();

        if (!$response) {
            throw new LocalizedException(__('No response from request to ' . $url));
        }

        $response = $this->json->unserialize($response);

        if (!empty($response['code'])) {
            throw new LocalizedException(__('Error returned with request to ' . $url . '. Code: ' . $response['code'] . ' Error: ' . $response['message']));
        }

        return $response['token'];
    }

    /**
     * @return array
     */
    public function getBody($quote): array
    {
        $totals = $this->priceFormat($quote->getGrandTotal());
        $domain = $this->storeManager->getStore()->getBaseUrl();
        $body = [
            'order' => [
                'order_id' => $quote->getId(),
                'currency' => $quote->getCurrency()->getQuoteCurrencyCode(),
                'tax_amount' => $this->priceFormat($quote->getCustomerTaxvat()),
                'items_total_amount' => $totals,
                'sub_total' => $totals + 1,
                'total_amount' => $totals + 2,
                'store_code' => 'all', //$this->storeManager->getStore()->getCode(),
                'items' => $this->getItems($quote),
                'discounts' => [],
                'shipping_options' => [
                    'type' => 'delivery'
                ],
                'webhook_urls' => [
                    'notify_order' => $domain . 'rest/V1/orders/notify',
                    'apply_coupon' => $domain . 'rest/V1/orders/{order_id}/coupons',
                    'remove_coupon' => $domain . 'rest/V1/orders/{order_id}/coupons/{coupon_code}',
                    'get_shipping_methods' => $domain . 'rest/V1/orders/{order_id}/shipping-methods',
                    'update_shipping_method' => $domain . 'duna/set/shippingmethod/order/{order_id}/method'
                ]
            ]
        ];
        return $body;
    }

    /**
     * @param $items
     * @return array
     */
    private function getItems($quote): array
    {
        $currencyCode = $quote->getCurrency()->getQuoteCurrencyCode();
        $currencySymbol = $this->priceCurrency->getCurrencySymbol();
        $items = $quote->getItemsCollection();
        $itemsList = [];
        foreach ($items as $item) {
            if ($item->getParentItem()) continue;
            $itemsList[] = [
                'id' => $item->getProductId(),
                'name' => $item->getName(),
                'description' => $item->getDescription(),
                'options' => '',
                'total_amount' => [
                    'amount' => $this->priceFormat($item->getRowTotal()),
                    'currency' => $currencyCode,
                    'currency_symbol' => $currencySymbol
                ],
                'unit_price' => [
                    'amount' => $this->priceFormat($item->getPrice()),
                    'currency' => $currencyCode,
                    'currency_symbol' => $currencySymbol
                ],
                'tax_amount' => [
                    'amount' => $this->priceFormat($item->getTaxAmount()),
                    'currency' => $currencyCode,
                    'currency_symbol' => $currencySymbol
                ],
                'quantity' => (int) $item->getQty(),
                'uom' => '',
                'upc' => '',
                'sku' => $item->getSku(),
                'isbn' => '',
                'brand' => '',
                'manufacturer' => '',
                'category' => $this->getCategory($item),
                'color' => '', # Confirmar con DUna
                'size' => '', # Confirmar con DUna
                'weight' => [
                    'weight' => $this->priceFormat($item->getWeight(), 2, '.', ''),
                    'unit' => $this->getWeightUnit()
                ],
                'image_url' => $this->getImageUrl($item),
                'type' => ($item->getIsVirtual() ? 'virtual' : 'physical'),
                'taxable' => true # Confirmar con DUna
            ];
        }
        return $itemsList;
    }

    /**
     * @param $price
     * @return int
     */
    private function priceFormat($price): int
    {
        $priceFix = number_format($price, 2, '.', '');
        return (int) $priceFix * 100;
    }

    /**
     * @return string
     */
    private function getWeightUnit(): string
    {
        return $this->helper->getConfigValue('general/locale/weight_unit');
    }

    /**
     * @param $item
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getImageUrl($item): string
    {
        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $thumbnail = $item->getProduct()->getThumbnail();
        return $mediaUrl . 'catalog/product' . $thumbnail;
    }

    /**
     * @param $item
     * @return string
     */
    private function getCategory($item): string
    {
        $categoriesIds = $item->getProduct()->getCategoryIds();
        foreach ($categoriesIds as $categoryId) {
            $category = $this->category->load($categoryId)->getName();
        }
        return $category;
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    private function tokenize(): string
    {
        $quote = $this->checkoutSession->getQuote();
        $body = $this->json->serialize($this->getBody($quote));
        $this->helper->log('debug', 'Json to Tokenize:', [$body]);
        return $this->request($body);
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getToken(): string
    {
        $token = $this->tokenize();
        $this->helper->log('debug', 'Token:', [$token]);
        return $token;
    }
}
