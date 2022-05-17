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
     * @param int $cartId
     * @return array|\Magento\Framework\Controller\Result\Json
     * @throws NoSuchEntityException
     */
    public function applycoupon(int $cartId);

    /**
     * Get removecoupon
     *
     * @param \DUna\Payments\Api\Data\CheckoutInterface $parameters parameters
     *
     *
     * @return array
     */
    public function removecoupon(\DUna\Payments\Api\Data\CheckoutInterface $parameters);
}
