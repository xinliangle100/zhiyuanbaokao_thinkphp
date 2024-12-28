<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
namespace think;
// 设置响应头，允许所有域名访问
header("Access-Control-Allow-Origin: *");

// 设置允许的请求方法，通常是 GET, POST, PUT, DELETE 等
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// 设置允许的请求头，客户端可以发送的请求头
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require __DIR__ . '/../vendor/autoload.php';

// 执行HTTP应用并响应
$http = (new App())->http;

$response = $http->run();

$response->send();

$http->end($response);
