<?php

namespace Swoole\Console;

class Process {

    /**
     * 设置进程的名称
     * @param $name
     */
    public static function setProcessName($name)
    {
        if (function_exists('cli_set_process_title')) {
            cli_set_process_title($name);
        } else if (function_exists('swoole_set_process_name')) {
            swoole_set_process_name($name);
        }
    }

    /**
     * 新建进程
     * @param callable $callback
     * @param bool $redirect_stdin_stdout
     * @param bool $create_pipe
     * @return \swoole_process
     */
    public static function createProcess(callable $callback, $redirect_stdin_stdout = false, $create_pipe = true)
    {
        return new \swoole_process($callback, $redirect_stdin_stdout, $create_pipe);
    }

}