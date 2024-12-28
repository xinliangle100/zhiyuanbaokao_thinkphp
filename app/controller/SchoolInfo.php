<?php

declare(strict_types=1);

namespace app\controller;

use think\Request;

namespace app\controller;

use think\facade\Db;
use think\response\Json;
use think\facade\Request;

use think\exception\DbException;

class SchoolInfo
{
    function getSchoolInfoByCode(): Json

    {
        $code = Request::param('code', 10001);
        try {        // 查询admin表中的所有数据
            $data = Db::table(table: '2024_yzy_schoolinfo')->where("code", $code)->select();


            $wrappedData = [
                'status' => 'success', // 可以返回一个状态码
                'data' => $data,     // 返回查询结果
                'message' => '数据查询成功'
            ];

            return Json($wrappedData); // 返回包裹后的数据

        } catch (\Exception $e) {
            // 异常处理
            return json([
                'status' => 'false',
                'message' => $e->getMessage()
            ]);
        }
    }
    function getSchoolListByCode(): Json
    {
        // 获取请求参数
        $code = Request::param('code', 10001);
        try {        // 查询admin表中的所有数据
            $data = Db::table(table: '2024_yzy_schoollist')->where("code", $code)->select();

            // 构造返回的 JSON 格式
            $response = [
                'data' => $data,
                'message' => '请求成功',
                'status' => 'success'
            ];

            return Json($response); // 返回包裹后的数据

        } catch (\Exception $e) {
            // 异常处理
            return json([
                'status' => 'false',
                'message' => $e->getMessage()
            ]);
        }
    }
}
