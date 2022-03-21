<?php

namespace DUna\Payments\Api\Data;

interface CheckoutInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    /**
     * Set user_id
     *
     * @param int $user_id user_id
     *
     * @return int
     */
    public function setUserId($user_id);

    /**
     * Get UserId
     *
     * @return int|null
     */
    public function getUserId();

    /**
     * Set setCartId
     *
     * @param int $cart_id cart_id
     *
     * @return int
     */
    public function setCartId($cart_id);
    /**
     * Get setType
     *
     * @return int|null
     */
    public function getCartId();

    /**
     * Set store_id
     *
     * @param int $store_id store_id
     *
     * @return int
     */
    public function setStoreId($store_id);
    /**
     * Get page
     *
     * @return int|null
     */
    public function getStoreId();

    /**
     * Set setCouponCode
     *
     * @param string $coupon_code coupon_code
     *
     * @return string
     */
    public function setCouponCode($coupon_code);
    /**
     * Get setType
     *
     * @return string|null
     */
    public function getCouponCode();
}
