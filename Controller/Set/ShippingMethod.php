<?php

namespace Deuna\Checkout\Controller\Set;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Deuna\Checkout\Api\ShippingMethodsInterface;

class ShippingMethod extends Action
{
    /**
     * @var ShippingMethodsInterface
     */
    private $shippingMethodsInterface;

    /**
     * @param Context $context
     * @param ShippingMethodsInterface $shippingMethodsInterface
     */
    public function __construct(
        Context $context,
        ShippingMethodsInterface $shippingMethodsInterface
    ) {
        parent::__construct($context);
        $this->shippingMethodsInterface = $shippingMethodsInterface;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|mixed
     */
    public function execute()
    {
        $order = $this->getRequest()->getParam('order');
        $method = $this->getRequest()->getParam('method');
        return $this->shippingMethodsInterface->set($order, $method);
    }
}
