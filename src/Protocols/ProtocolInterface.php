<?php

namespace Swoole\Protocols;

interface ProtocolInterface {

    const PROTOCOLS_MODE_JSON = 1;
    
    const PROTOCOLS_MODE_SERIALIZE = 2;

    static function encode($buffer, $guid);
    
    static function decode($buffer);
    
}