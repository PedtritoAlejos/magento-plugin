<?php

namespace DUna\Payments\Plugin\Checkout;

use \Magento\Framework\Exception\NotFoundException;

class Index
{
    /**
     * @var $url
     */
    private $url;

    /**
     * @var $helperData
     */
    protected $helperData;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\UrlInterface $url
     * @param \DUna\Payments\Helper\Data $helperData
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\UrlInterface $url,
        \DUna\Payments\Helper\Data $helperData
    ) {
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        $this->resultFactory = $context->getResultFactory();
        $this->url = $url;
        $this->helperData = $helperData;
    }

    /**
     * @param \Magento\Checkout\Controller\Index\Index $subject
     * @param \Closure $proceed
     * @return mixed
     */
    public function aroundExecute(\Magento\Checkout\Controller\Index\Index $subject, \Closure $proceed)
    {
        $isModuleEnable = $this->helperData->getGeneralConfig('enable');
        $returnDefault = $proceed();
        if ($isModuleEnable == '1') {
            $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
            $norouteUrl = $this->url->getUrl('checkout/cart');
            $result = $resultRedirect->setUrl($norouteUrl);
            return $result;
        }
        return $returnDefault;
    }

}
