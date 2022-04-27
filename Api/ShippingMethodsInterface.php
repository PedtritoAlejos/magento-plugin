<?php

namespace DUna\Payments\Api;

interface ShippingMethodsInterface
{
    /**
     * Loads shipping methods.
     *
     * @params int $cartId
     * @return array
     */
    public function get(int $cartId);

    /**
     * @param int $cartId
     * @param string $code
     * @return mixed
     */
    public function set(int $cartId, string $code);
}
