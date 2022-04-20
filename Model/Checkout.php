<?php

namespace DUna\Payments\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Ced\CsMultiShipping\Model\Shipping;


class Checkout
{
    protected $objectManager;

    protected $scopeConfig;

    protected $storeManager;

    protected $quoteRepository;

    protected $cart;

    protected $quoteFactory;

    protected $_tokenFactory;

    public function __construct(
        \Magento\Quote\Model\QuoteFactory                  $quoteFactory,
        \Magento\Framework\ObjectManagerInterface          $objectInterface,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface         $storeManager,
        \Magento\Checkout\Model\Cart                       $cart,
        \Magento\Quote\Api\CartRepositoryInterface         $quoteRepository,
        \Magento\Integration\Model\Oauth\TokenFactory      $tokenFactory
    )
    {
        $this->quoteFactory = $quoteFactory;
        $this->objectManager = $objectInterface;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->quoteRepository = $quoteRepository;
        $this->cart = $cart;
        $this->_tokenFactory = $tokenFactory;
    }

    /**
     * getCartmobi
     *
     * @param array $data data
     *
     * @return array
     */
    function _getQuotemobi($data)
    {
        $token = isset($data['user_token']) ? $data['user_token'] : '0';
        $tokenObj = $this->_tokenFactory->create()->load($token, 'token');
        $customer_id = null;
        if (!$tokenObj->getId()) {
            throw new \Magento\Framework\Oauth\Exception(
                __('Specified token does not exist')
            );
        } else {
            $customer_id = $tokenObj->getCustomerId();
            if (!$customer_id) {
                throw new \Magento\Framework\Oauth\Exception(
                    __('Specified token customer does not exist')
                );
            }
        }
        $customer_id = isset($customer_id) ? $customer_id : '0';
        $quote = null;

        if ($customer_id) {
            try {
                $quote = $this->quoteRepository->getForCustomer($customer_id);
                $this->cart->setQuote($quote);
                $this->isLoggedIn = true;

            } catch (NoSuchEntityException $e) {

                $store = $this->storeManager->getStore();
                $quote = $this->objectManager->create('\Magento\Quote\Model\QuoteFactory')->create();
                $quote->setStore($store);

                try {
                    $customerd = $this->objectManager->create('\Magento\Customer\Api\CustomerRepositoryInterface')
                        ->getById($customer_id);
                    $quote->setCurrency();
                    $quote->assignCustomer($customerd);
                    $this->cart->getQuote()->collectTotals();
                    $this->cart->setQuote($quote);

                } catch (NoSuchEntityException $e) {
                    $logData = ['title' => 'checkout_error', 'description' => $e->getMessage()];
                    return ['success' => false, 'message' => 'Please contact admin for the error'];
                }

            } catch (Exception $e) {
                return ['success' => false, 'message' => $e->getMessage()];
            }
        } else {
            $cart_id = isset($data['cart_id']) ? $data['cart_id'] : '0';
            if ($cart_id) {
                try {
                    $quote = $this->quoteRepository->get($cart_id);
                    return ['success' => true, 'quote' => $quote];

                } catch (NoSuchEntityException $e) {

                    $quote = $this->quoteFactory->create();
                    $this->cart->setQuote($quote);

                    return ['success' => true, 'quote' => $quote];
                } catch (Exception $e) {
                    return ['success' => false, 'message' => $e->getMessage()];
                }
            } else {
                $quote = $this->quoteFactory->create();
                return ['success' => true, 'quote' => $quote];
            }
        }
        if (is_object($quote) && $quote->getEntityId()) {
            return ['success' => true, 'quote' => $quote];
        } else {
            $quote = $this->quoteFactory->create();
            $this->cart->setQuote($quote);
            return ['success' => true, 'quote' => $quote];
        }
    }

    /**
     * Initialize coupon
     *
     * @param array $data data
     *
     * @return array
     */
    public function applycoupon($data)
    {
        $quote = $this->_getQuotemobi($data);
        if (isset($quote['success']) && $quote['success']) {
            $quote = $quote['quote'];
        } else {
            return ['code' => '0', 'success' => false, 'message' => "We can't find the quote item."];
        }
        if (!$quote->getItemsCount()) {
            $jsonData = ['code' => '0', 'result' => ['success' => false, 'message' => 'You have no items in your shopping cart.']];
            return $jsonData;
        }

        $couponCode = (string)isset($data['coupon_code']) ? $data['coupon_code'] : '';
        if (isset($data['remove']) && $data['remove'] == 1) {
            $couponCode = '';
        }
        $oldCouponCode = $quote->getCouponCode();
        if (!strlen($couponCode) && !strlen($oldCouponCode)) {
            $jsonData = ['code' => '0', 'result' => ['success' => false, 'message' => 'We cannot apply the coupon code.']];
            return $jsonData;
        }

        try {
            $quote->getShippingAddress()->setCollectShippingRates(true);
            $quote->setCouponCode($couponCode)->collectTotals()->save();
            if ($couponCode) {
                if ($couponCode == $quote->getCouponCode()) {

                    $jsonData = ['code' => '1', 'result' => ['success' => true, 'message' => 'You used coupon code ' . strip_tags($couponCode)]];
                    return $jsonData;

                } else {
                    $jsonData = ['code' => '0', 'result' => ['success' => false, 'message' => 'The coupon code ' . strip_tags($couponCode) . ' is not valid.']];
                    return $jsonData;
                }
            } else {
                $jsonData = ['code' => '1', 'result' => ['success' => true, 'message' => 'You canceled the coupon code.']];
                return $jsonData;
            }

        } catch (Exception $e) {
            $logData = ['title' => 'Apply Coupon', 'description' => $e->getMessage()];

            $jsonData = ['code' => '0', 'result' => ['success' => false, 'message' => 'We cannot apply the coupon code.']];
            return $jsonData;
        }
    }

    /**
     * Initialize coupon
     *
     * @param array $data data
     *
     * @return array
     */
    public function removecoupon($data)
    {
        $quote = $this->_getQuotemobi($data);
        if (isset($quote['success']) && $quote['success']) {
            $quote = $quote['quote'];
        } else {
            return ['code' => '0', 'success' => false, 'message' => "We can't find the quote item."];
        }

        if (!$quote->getItemsCount()) {
            $jsonData = ['code' => '0', 'result' => ['success' => false, 'message' => 'You have no items in your shopping cart.']];
            return $jsonData;
        }

        $couponCode = (string)isset($data['coupon_code']) ? $data['coupon_code'] : '';
        if (isset($data['remove']) && $data['remove'] == 1) {
            $couponCode = '';
        }

        try {
            $oldCouponCode = $quote->getCouponCode();
            $codeLength = strlen($oldCouponCode);

            if ($codeLength && $couponCode == $oldCouponCode) {
                $couponCode = '';
                $quote->getShippingAddress()->setCollectShippingRates(true);
                $quote->setCouponCode($couponCode)->collectTotals()->save();

                $jsonData = ['code' => '1', 'result' => ['success' => true, 'message' => 'You canceled the coupon code.']];
                return $jsonData;
            } else {
                    $jsonData = ['code' => '0', 'result' => ['success' => false, 'message' => 'The coupon code ' . strip_tags($couponCode) . ' is not valid.']];
                    return $jsonData;
            }

        } catch (Exception $e) {
            $jsonData = ['code' => '0', 'result' => ['success' => false, 'message' => 'We cannot remove the coupon code.']];
            return $jsonData;
        }
    }
}
