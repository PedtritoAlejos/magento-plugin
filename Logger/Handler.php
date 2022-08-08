<?php

namespace Deuna\Checkout\Logger;

use Magento\Framework\Logger\Handler\Base;

class Handler extends Base
{
    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/duna.log';
}
