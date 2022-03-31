<?php


namespace DUna\Payments\Api;
/**
 * CheckoutInterface
 *
 * @category Interface
 * @package  CheckoutInterface
 */
interface CheckoutInterface
{
    /**
     * Gets the json.
     *
     * @api
     * @param \DUna\Payments\Api\Data\CheckoutInterface $parameters parameters
     *
     * @return array
     */
    public function applycoupon(\DUna\Payments\Api\Data\CheckoutInterface $parameters);

}
