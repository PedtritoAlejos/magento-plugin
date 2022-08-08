<?php

namespace Deuna\Checkout\Model\Api;

class Checkout
{

    protected $checkout;

    public function __construct(
        \Deuna\Checkout\Model\Checkout $checkout
    )
    {
        $this->checkout = $checkout;
    }

    /**
     * Gets the json.
     *
     * @param \Deuna\Checkout\Api\Data\CheckoutInterface $parameters parameters
     *
     * @return []
     */
    public function applycoupon(\Deuna\Checkout\Api\Data\CheckoutInterface $parameters)
    {
        $data = $parameters->getData();
        if ($parameters && $parameters->getData()) {
            $deviceObject = $this->checkout->applycoupon($parameters->getData());
            return array($deviceObject);
        } else {
            return array(['success' => false]);
        }

    }

    /**
     * Gets the json.
     *
     * @param \Deuna\Checkout\Api\Data\CheckoutInterface $parameters parameters
     *
     * @return []
     */
    public function removecoupon(\Deuna\Checkout\Api\Data\CheckoutInterface $parameters)
    {
        $data = $parameters->getData();
        if ($parameters && $parameters->getData()) {
            $deviceObject = $this->checkout->removecoupon($parameters->getData());
            return array($deviceObject);
        } else {
            return array(['success' => false]);
        }

    }
}
