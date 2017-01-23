<?php

namespace Swoole;

class Tool {

    /**
     * 解析地址
     * @param $address
     * @return mixed
     */
    public static function parse_address($address)
    {
        if (false === ($info = parse_url($address))) {
            trigger_error('address [%s] error', E_USER_ERROR);
        }

        return $info;
    }

}