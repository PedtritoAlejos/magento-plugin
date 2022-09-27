<?php
namespace DUna\Payments\Model;

use Magento\Framework\Webapi\Rest\Request;
use Psr\Log\LoggerInterface;
use DUna\Payments\Model\OrderTokens;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteFactory as Quote;
use Magento\Quote\Api\CartRepositoryInterface as CRI;
use DUna\Payments\Helper\Data;

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

    /**
     * @var Data
     */
    protected $helper;

    public function __construct(
        Request $request,
        LoggerInterface $logger,
        QuoteManagement $quoteManagement,
        QuoteFactory $quoteFactory,
        OrderTokens $orderTokens,
        Quote $quoteModel,
        CRI $cri,
        Data $helper
    ) {
        $this->request = $request;
        $this->logger = $logger;
        $this->quoteManagement = $quoteManagement;
        $this->quoteFactory = $quoteFactory;
        $this->orderTokens = $orderTokens;
        $this->quoteModel = $quoteModel;
        $this->cri = $cri;
        $this->helper = $helper;
    }

    /**
     * @return false|string
     */
    public function notify()
    {
        $bodyReq = $this->request->getBodyParams();

        $this->helper->log('debug', 'Notify New Order:', $bodyReq);

        $order = $bodyReq['order'];
        $payment_status = $order['payment_status'];
        $token = $order['token'];
        $paymentProcessor = $order['payment']['data']['processor'];
        $metadata = $order['payment']['data']['metadata'];
        $paymentMethod = $order['payment_method'];
        $userComment = $order['user_instructions'];
        $authCode = isset($metadata['authorization_code']) ? $metadata['authorization_code'] : 'N/A';

        $quote = $this->quotePrepare($order);

        if ($quote) {
            $active = $quote->getIsActive();

            if ($active) {
                $order = $this->quoteManagement->submit($quote);

                if ($payment_status == 'processed') {
                    $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true)
                          ->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
                }

                if(!empty($userComment)) {
                    $order->addStatusHistoryComment(
                        "Comentario de cliente<br>
                        <i>{$userComment}</i>"
                    )->setIsVisibleOnFront(true);
                }

                $order->addStatusHistoryComment(
                    "Payment Processed by <strong>DEUNA Checkout</strong><br>
                    <strong>Token:</strong> {$token}<br>
                    <strong>Auth Code:</strong> {$authCode}<br>
                    <strong>Payment Method:</strong> {$paymentMethod}<br>
                    <strong>Processor:</strong> {$paymentProcessor}"
                );

                $order->save();
            } else {
                $order = json_encode(['result' => 'success']);
            }
        }

        return $order;
    }

    /**
     * @param $order
     * @return \Magento\Quote\Api\Data\CartInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function quotePrepare($order)
    {
        $quoteId = $order['order_id'];

        $email = $order['payment']['data']['customer']['email'];

        $quote = $this->cri->get($quoteId);

        $quote->getPayment()->setMethod('deuna_payments');

        $quote->setCustomerEmail($email);

        return $quote;
    }

    /**
     * @return false|string
     */
    public function getToken()
    {
        $json = [
            'orderToken' => $this->orderTokens->getToken(),
        ];

        return json_encode($json);
    }

}
