<?php

namespace Deuna\Checkout\Api;

interface PostManagementInterface {

    /**
     * @return mixed
     */
    public function notify();

    /**
     * @return mixed
     */
    public function getToken();
}
