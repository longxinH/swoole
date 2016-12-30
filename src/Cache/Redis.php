<?php

namespace Swoole\Cache;

class Redis implements CacheInterface {
    /**
     * @var Redis
     */
    protected static $instance;

    protected $host;

    protected $port;

    /**
     * @var \Redis
     */
    protected $redis;
    
    private function __construct($host, $port, $auth = false, $db = '')
    {
        try {
            $this->redis = new \Redis();
            if ($this->redis->connect($host, $port) === false) {
                throw new \Exception("redis connect error.");
            }

            if ($auth) {
                $this->redis->auth($auth);
            }

            if ($db) {
                $this->selectDb($db);
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        $this->host = $host;
        $this->port = $port;
    }

    /**
     * @param array $config
     * @return Redis
     */
    public static function getInstance(array $config)
    {
        $host = $config['host'];
        $port = $config['port'];
        $db = isset($config['db']) ? $config['db'] : '';
        $pwd = isset($config['auth']) ? $config['auth'] : false;
        
        $key = $host . '_' . $port . ($db ? '_' . $db : '');
        
        if (!isset(self::$instance[$key])) {
            self::$instance[$key] = new self($host, $port, $pwd, $db);
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
