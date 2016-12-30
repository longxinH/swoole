<?php

namespace Swoole\Protocols;


use Swoole\Packet\Format;

class Json implements ProtocolInterface {
    
    public static $instance;

    const PROTOCOLS_MODE = 1;

    public static function getInstance()
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * 打包
     * @param $value
     * @param $guid
     * @return string
     */
    public static function encode($value, $guid)
    {
        $value = json_encode($value);
        return pack('NNN', strlen($value), self::PROTOCOLS_MODE, $guid) . $value;
    }

    /**
     * 解包
     * @param $json
     * @return array
     */
    public static function decode($json)
    {
        $json = substr($json, Format::HEADER_SIZE);
        return json_decode($json, true);
    }

}