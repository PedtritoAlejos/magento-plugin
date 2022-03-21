<?php

namespace DUna\Payments\Model\Api;

class Checkout
{

    protected $checkout;

    public function __construct(
        \DUna\Payments\Model\Checkout $checkout
    )
    {
        $this->checkout = $checkout;
    }

    /**
     * Gets the json.
     *
     * @param \DUna\Payments\Api\Data\CheckoutInterface $parameters parameters
     *
     * @return []
     */
    public function applycoupon(\DUna\Payments\Api\Data\CheckoutInterface $parameters)
    {
        $data = $parameters->getData();
        if ($parameters && $parameters->getData()) {
            $deviceObject = $this->checkout->applycoupon($parameters->getData());
            return array($deviceObject);
        } else {
            return array(['success' => false]);
        }

    }
}
