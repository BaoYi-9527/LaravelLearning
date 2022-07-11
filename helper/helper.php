<?php

# win/linux 文件路径处理
if (!function_exists('os_path')) {
    function os_path($path)
    {
        if (PHP_OS == 'Linux') {
            $path = str_replace('\\', DIRECTORY_SEPARATOR, $path);
        } else {
            $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
        }
        return $path;
    }
}

# 判断当前脚本环境是否为Cli
if (!function_exists('isCliConsole')) {
    function is_cli()
    {
        $result = strpos(php_sapi_name(), 'cli');
        return $result !== false;
    }
}
