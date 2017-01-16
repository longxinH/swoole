<?php

namespace Swoole\Server;

interface ServerInterface {

    /**
     * @return string
     */
    public function getHost();

    /**
     * @return string
     */
    public function getPort();

    /**
     * @return string
     */
    public function getServerHost();

    /**
     * @return string
     */
    public function getProcessName();

    /**
     * @return string
     */
    public function getMasterPidFile();

    /**
     * @return string
     */
    public function getManagerPidFile();



}