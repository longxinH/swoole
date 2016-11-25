<?php

namespace Swoole\Cache;

class Redis implements CacheInterface {
    /**
     * @var Redis
     */
    protected static $instance;

    /**
     * @var \Redis
     */
    protected $redis;
    
    private function __construct($host, $port, $auth = false)
    {
        try {
            $this->redis = new \Redis();
            $this->redis->connect($host, $port);
            if ($auth) {
                $this->redis->auth($auth);
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @param array $config
     * @return Redis
     */
    public static function getInstance(array $config)
    {
        $host = $config['host'];
        $port = $config['port'];
        $pwd = isset($config['auth']) ? $config['auth'] : false;
        
        $key = $host . '_' . $port;
        
        if (!isset(self::$instance[$key])) {
            self::$instance[$key] = new self($host, $port, $pwd);
        }

        return self::$instance[$key];
    }

    /**
     * @return \Redis
     */
    public function getHandle()
    {
        return $this->redis;
    }

    public function selectDb($db)
    {
        $this->redis->select($db);
    }

    public function get($key)
    {
        return $this->redis->get($key);
    }

    public function set($key, $val, $ttl = 0)
    {
        if ($ttl) {
            return $this->redis->setex($key, $ttl, $val);
        } else {
            return $this->redis->set($key, $val);
        }
    }

    public function del($key)
    {
        return $this->redis->del($key);
    }

    public function __call($name, $arguments)
    {
      return call_user_func_array([$this->redis, $name], $arguments);
    }
}
