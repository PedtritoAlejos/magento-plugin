<?php
namespace DUna\Payments\Model;

use Magento\Framework\Webapi\Rest\Request;
use Psr\Log\LoggerInterface;
use DUna\Payments\Model\OrderTokens;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteFactory as Quote;
use Magento\Quote\Api\CartRepositoryInterface as CRI;

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

    protected $quoteModel;
    protected $cri;

    public function __construct(
        Request $request,
        LoggerInterface $logger,
        QuoteManagement $quoteManagement,
        QuoteFactory $quoteFactory,
        OrderTokens $orderTokens,
        Quote $quoteModel,
        CRI $cri
    ) {
        $this->request = $request;
        $this->logger = $logger;
        $this->quoteManagement = $quoteManagement;
        $this->quoteFactory = $quoteFactory;
        $this->orderTokens = $orderTokens;
        $this->quoteModel = $quoteModel;
        $this->cri = $cri;
    }

    /**
     * @return false|string
     */
    public function notify()
    {
        $bodyReq = $this->request->getBodyParams();
        $order = $bodyReq['order'];
        $payment_status = $order['payment_status'];

        $quote = $this->quotePrepare($order);
        if ($quote) {
            $active = $quote->getIsActive();
            if ($active) {
                $order = $this->quoteManagement->submit($quote);
            } else {
                $order = ['success'];
            }
        }

        if ($payment_status == 'processed') {
            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true)
                ->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
            $order->save();
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
        $quote = $this->cri->get($quoteId);
        $quote->getPayment()->setMethod('duna_payments');

        $shippingAddress = $quote->getShippingAddress();
        $billingAddress = $quote->getBillingAddress();
        $shippingAddress->setRegionId(941);
        $shippingAddress->setShippingMethod('freeshipping');
        $shippingAddress->setShippingDescription('Free Shipping - Free');
        $billingAddress->setShippingMethod('freeshipping');
        $shippingAddress->setCollectShippingRates(true);
        $shippingAddress->save();


        $quote->getShippingAddress()->setShippingMethod('freeshipping_freeshipping');

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
