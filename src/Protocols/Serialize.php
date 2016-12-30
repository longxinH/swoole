<?php

namespace Swoole\Protocols;

use Swoole\Packet\Format;

class Serialize implements ProtocolInterface {
    
    public static $instance;

    const PROTOCOLS_MODE = 2;

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
        $value = serialize($value);
        return pack('NNN', strlen($value), self::PROTOCOLS_MODE, $guid) . $value;
    }

    /**
     * 解包
     * @param $str
     * @return bool|array
     */
    public static function decode($str)
    {
        $str = substr($str, Format::HEADER_SIZE);
        return unserialize($str);

    }

}