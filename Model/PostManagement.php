<?php
namespace DUna\Payments\Model;

use Magento\Framework\Webapi\Rest\Request;
use Psr\Log\LoggerInterface;
use DUna\Payments\Model\OrderTokens;

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

    public function __construct(
        Request $request,
        LoggerInterface $logger,
        OrderTokens $orderTokens
    ) {
        $this->request = $request;
        $this->logger = $logger;
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
        return json_encode($order);
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
