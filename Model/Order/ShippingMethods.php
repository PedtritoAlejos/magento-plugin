<?php

namespace DUna\Payments\Model\Order;

use Magento\Framework\Exception\NoSuchEntityException;
use DUna\Payments\Api\ShippingMethodsInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Webapi\Rest\Request;
use DUna\Payments\Helper\Data;


/**
 * Class ShippingMethods
 */
class ShippingMethods implements ShippingMethodsInterface
{
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * Shipping method converter
     *
     * @var ShippingMethodConverter
     */
    protected $converter;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Directory\Model\Currency
     */
    protected $_currency;

    /**
     * @var \Magento\Quote\Api\ShippingMethodManagementInterface
     */
    protected $shippingMethodManagementInterface;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;


    protected $_scopeConfig;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Quote\Model\Cart\ShippingMethodConverter $converter
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\Cart\ShippingMethodConverter $converter,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Directory\Model\Currency $currency,
        \Magento\Quote\Api\ShippingMethodManagementInterface $shippingMethodManagementInterface,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Data $helper,
        Request $request
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->converter = $converter;
        $this->storeManager = $storeManager;
        $this->_currency = $currency;
        $this->shippingMethodManagementInterface = $shippingMethodManagementInterface;
        $this->productRepository = $productRepository;
        $this->_scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->request = $request;
    }

    private function setShippingInfo($quote)
    {
        $body = $this->request->getBodyParams();

        $shippingAddress = $quote->getShippingAddress();

        $shippingAddress->setFirstname($body['first_name']);
        $shippingAddress->setLastname($body['last_name']);
        $shippingAddress->setTelephone($body['phone']);
        $shippingAddress->setStreet($body['address1']);
        $shippingAddress->setCity($body['city']);
        $shippingAddress->setPostcode($body['zipcode']);
        $shippingAddress->setCountryId($body['country_iso']);
        $shippingAddress->save();
    }

    /**
     * Returns Shipping Methods
     *
     * @params int $cartId
     * @return array
     * @throws NoSuchEntityException
     */
    public function get(int $cartId)
    {
        $output = [];

        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        $this->setShippingInfo($quote);

        // no methods applicable for empty carts or carts with virtual products
        if ($quote->isVirtual() || 0 == $quote->getItemsCount()) {
            return [];
        }

        $quote->collectTotals();
        $shippingAddress = $quote->getShippingAddress();
        if (!$shippingAddress->getCountryId()) {
            throw new StateException(__('The shipping address is missing. Set the address and try again.'));
        }
        $shippingAddress->setCollectShippingRates(true);
        $shippingAddress->collectShippingRates();
        $shippingRates = $shippingAddress->getGroupedAllShippingRates();
        foreach ($shippingRates as $carrierRates) {
            foreach ($carrierRates as $rate) {
                $output[] = $this->converter->modelToDataObject($rate, $quote->getQuoteCurrencyCode());
            }
        }

        $shippingMethods = [
            'shipping_methods' => []
        ];
        foreach ($output as $method) {
            $shippingMethods['shipping_methods'][] = [
                "code" => $method->getMethodCode(),
                "name" => $method->getMethodTitle(),
                "cost" => $method->getAmount(),
                "tax_amount" => $method->getPriceInclTax(),
                "min_delivery_date" => "",
                "max_delivery_date" => ""
            ];
        }
        $this->helper->log('debug', 'Shipping Methods:', $shippingMethods);
        die(json_encode($shippingMethods));
    }

    /**
     * @param int $cartId
     * @param string $code
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function set(int $cartId, string $code) {
        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        $shippingMethods = $this->shippingMethodManagementInterface->getList($cartId);
        $shippingAmount = 0;
        foreach ($shippingMethods as $shippingMethod) {
            if ($shippingMethod->getMethodCode() == $code) {
                $shippingAmount = $shippingMethod->getAmount();
                break;
            }
        }

        $address = $quote->getShippingAddress() ?: $quote->getBillingAddress();
        $response = [
            "order" => [
                "order_id" => $quote->getId(),
                "store_code" => $this->storeManager->getStore()->getCode(),
                "currency" => $quote->getQuoteCurrencyCode(),
                "tax_amount" => $quote->getStoreToQuoteRate(),
                "shipping_amount" => $shippingAmount,
                "items_total_amount" => $quote->getItemsCount(),
                "sub_total" => $quote->getSubtotal(),
                "total_amount" => $quote->getGrandTotal(),
                "items" => $this->getItems($quote),
                "discounts" => [
                    [
                        "amount" => 0,
                        "display_amount" => "",
                        "code" => "",
                        "reference" => "",
                        "description" => "",
                        "details_url" => "",
                        "free_shipping" => [
                            "is_free_shipping" => false,
                            "maximum_cost_allowed" => 0
                        ],
                        "discount_category" => ""
                    ]
                ],
                "shipping_address" => $this->getShippingAddress($address),
                "shipping_options" => null,
                "user_instructions" => "",
                "metadata" => [
                    "key1" => "",
                    "key2" => ""
                ],
                "status" => $quote->getIsActive(),
                "payment" => null
            ]
        ];

        return $response;
    }

    /**
     * @throws NoSuchEntityException
     */
    protected function getItems($quote)
    {
        $items = [];
        foreach ($quote->getItems() as $item) {
            try {
                $product = $this->productRepository->get($item->getSku());
                $items[] = [
                    "id" => $item->getItemId(),
                    "name" => $item->getName(),
                    "description" => $item->getDescription(),
                    "options" => "",
                    "total_amount" => [
                        "amount" => ($item->getPrice() * $item->getQty()),
                        "original_amount" => ($product->getPrice() * $item->getQty()),
                        "currency" => $quote->getQuoteCurrencyCode(),
                        "currency_symbol" => $this->_currency->getCurrencySymbol()
                    ],
                    "unit_price" => [
                        "amount" => $item->getPrice(),
                        "currency" => $quote->getQuoteCurrencyCode(),
                        "currency_symbol" => $this->_currency->getCurrencySymbol()
                    ],
                    "tax_amount" => [
                        "amount" => $quote->getStoreToQuoteRate(),
                        "currency" => $quote->getQuoteCurrencyCode(),
                        "currency_symbol" => $this->_currency->getCurrencySymbol()
                    ],
                    "quantity" => $item->getQty(),
                    "uom" => $product->getUom(),
                    "upc" => $product->getUpc(),
                    "sku" => $item->getSku(),
                    "isbn" => $product->getIsbn(),
                    "brand" => $product->getBrand(),
                    "manufacturer" => $product->getManufacturer(),
                    "category" => implode(', ', $product->getCategoryIds()),
                    "color" => $product->getColor(),
                    "size" => $product->getSize(),
                    "weight" => [
                        "weight" => $product->getWeight(),
                        "unit" => $this->_scopeConfig->getValue(
                            'general/locale/weight_unit',
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                        )
                    ],
                    "image_url" => $product->getProductUrl(),
                    "details_url" => "",
                    "type" => $item->getProductType(),
                    "taxable" => (bool)$quote->getStoreToQuoteRate()
                ];
            } catch (\Exception $e) {
                throw new CouldNotSaveException(__('The shipping method can\'t be set. %1', $e->getMessage()));
            }
        }

        return $items;
    }

    protected function getShippingAddress($shippingAddress)
    {
        return [
            "id" => $shippingAddress->getId(),
            "user_id" => $shippingAddress->getCustomerId(),
            "first_name" => $shippingAddress->getFirstName(),
            "last_name" => $shippingAddress->getLastName(),
            "phone" => $shippingAddress->getTelephone(),
            "identity_document" => $shippingAddress->getIdentityDocument(),
            "lat" => $shippingAddress->getLat(),
            "lng" => $shippingAddress->getLng(),
            "address1" => $shippingAddress->getStreetLine(1),
            "address2" => $shippingAddress->getStreetLine(2),
            "city" => $shippingAddress->getCity(),
            "zipcode" => $shippingAddress->getPostcode(),
            "state_name" => $shippingAddress->getRegion(),
            "country_code" => $shippingAddress->getCountryId(),
            "additional_description" => $shippingAddress->getAdditionalDescription(),
            "address_type" => $shippingAddress->getAddressType(),
            "is_default" => (bool)$shippingAddress->getIsDefaultShipping(),
            "created_at" => $shippingAddress->getCreatedAt(),
            "updated_at" => $shippingAddress->getUpdatedAt()
        ];
    }
}
