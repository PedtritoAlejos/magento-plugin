<?php

namespace DUna\Payments\Model\Api;


class CheckoutData extends \Magento\Framework\Model\AbstractExtensibleModel implements
    \DUna\Payments\Api\Data\CheckoutInterface
{
    /**#@+
     * Constants
     */
    const KEY_STORE_ID ='store_id';
    const KEY_CART_ID ='cart_id';
    const KEY_COUPON_CODE ='coupon_code';
    const KEY_USER_ID ='user_id';

    /**
     * getUserId
     *
     * @return string $this this
     */
    public function getUserId()
    {
        return $this->getData(self::KEY_USER_ID);
    }

    /**
     * Set password
     *
     * @param string $user_id user_id
     *
     * @return string $this this
     */
    public function setUserId($user_id)
    {
        return $this->setData(self::KEY_USER_ID, $user_id);
    }

    /**
     * getBillingaddress
     *
     * @return string $this this
     */
    public function getStoreId()
    {
        return $this->getData(self::KEY_STORE_ID);
    }

    /**
     * Set store_id
     *
     * @param string $store_id store_id
     *
     * @return $this
     */
    public function setStoreId($store_id)
    {
        return $this->setData(self::KEY_STORE_ID, $store_id);
    }

    /**
     * getBillingaddress
     *
     * @return string $this this
     */
    public function getCartId()
    {
        return $this->getData(self::KEY_CART_ID);
    }

    /**
     * setCartId
     *
     * @param int $cart_id cart_id
     *
     * @return $this
     */
    public function setCartId($cart_id)
    {
        return $this->setData(self::KEY_CART_ID, $cart_id);
    }
    /**
     * getCouponCode
     *
     * @return string $this this
     */
    public function getCouponCode()
    {
        return $this->getData(self::KEY_COUPON_CODE);
    }

    /**
     * setCouponCode
     *
     * @param string $coupon_code coupon_code
     *
     * @return $this
     */
    public function setCouponCode($coupon_code)
    {
        return $this->setData(self::KEY_COUPON_CODE, $coupon_code);
    }
}

