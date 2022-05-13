<?php
namespace DUna\Payments\Model;

use Magento\Framework\Webapi\Rest\Request;
use Psr\Log\LoggerInterface;
use DUna\Payments\Model\OrderTokens;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Model\QuoteFactory;



class PostManagement {

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var OrderTokens
     */
    private $orderTokens;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;

    public function __construct(
        Request $request,
        LoggerInterface $logger,
        QuoteManagement $quoteManagement,
        QuoteFactory $quoteFactory,
        OrderTokens $orderTokens
    ) {
        $this->request = $request;
        $this->logger = $logger;
        $this->quoteManagement = $quoteManagement;
        $this->quoteFactory = $quoteFactory;
        $this->orderTokens = $orderTokens;
    }

    /**
     * @return false|string
     */
    public function notify()
    {
        $bodyReq = $this->request->getBodyParams();
        $order = $bodyReq['order'];
        $orderid = $order['order_id'];

        $quote = $this->quotePrepare($order);
        if ($quote) {
            $order = $this->quoteManagement->submit($quote);
        }

        return json_encode($order);
    }

    /**
     * @param $order
     * @return \Magento\Quote\Model\Quote
     */
    private function quotePrepare($order)
    {
        $quoteId = $order['order_id'];
        $email = $order['payment']['data']['customer']['email'];
        $quote = $this->quoteFactory->create()->load($quoteId);
        $quote->getPayment()->setMethod('duna_payments');

        $shippingAddress = $quote->getShippingAddress();
        $billingAddress = $quote->getBillingAddress();
        $shippingAddress->setRegionId(941);
        $shippingAddress->setShippingMethod('freeshipping');
        $shippingAddress->setShippingDescription('Free Shipping - Free');
        $billingAddress->setShippingMethod('freeshipping');
        $shippingAddress->setCollectShippingRates(true);
        $shippingAddress->save();

        $quote->setShippingAddress($shippingAddress);

        $quote->setCustomerId(null);
        $quote->setCustomerEmail($email);
        $quote->setCustomerIsGuest(true);
        $quote->setCustomerGroupId(\Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID);

        return $quote;
    }

    /**
     * @return false|string
     */
    public function getToken()
    {
        $json = ['orderToken' => $this->orderTokens->getToken()];
        return json_encode($json);
    }

}
