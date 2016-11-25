<?php

namespace Swoole\Cache;

interface CacheInterface {

    static public function getInstance(array $config);

    public function getHandle();

    public function get($key);

    public function set($key, $val);

    public function del($key);
    
}