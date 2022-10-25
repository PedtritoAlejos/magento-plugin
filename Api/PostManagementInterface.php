<?php

namespace DUna\Payments\Api;

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
