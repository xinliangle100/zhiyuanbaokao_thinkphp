<?php

declare(strict_types=1);

namespace app\controller;

use think\facade\Db;
use think\response\Json;
use think\facade\Request;

use think\exception\DbException;
use app\controller\DateTime;

class User
{

    function getAllProvince(): Json
    {
        try {        // 查询admin表中的所有数据
            $province_name = Db::table(table: '2024_yzy_schoollist')->order('province_code', 'esc')->distinct(true)->field('province_code,province_name')->select();

            // 如果你希望对查询结果进行进一步的处理或者格式化
            // 比如按特定格式包装数据
            $wrappedData = [
                'status' => 'success', // 可以返回一个状态码
                'data' => $province_name,     // 返回查询结果
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
    function getSchoolByQuery()
    {
        // 获取请求参数
        $selectedProvince = Request::param('selectedProvince', []);
        $selectLableNames = Request::param('selectLableNames', []);
        $searchKey = Request::param('searchKey', '');
        $page = Request::param('page', 1); // 当前页，默认为 1
        $pageSize = Request::param('pageSize', 20); // 每页数据数量，默认为 20

        // 处理 "不限" 条件的默认值
        if (in_array('不限', $selectedProvince)) {
            $selectedProvince = []; // 不做省份筛选
        }

        if (in_array('不限', $selectLableNames)) {
            $selectLableNames = []; // 不做标签筛选
        }

        // 开始构建查询
        $query = Db::name('2024_yzy_schoollist'); // 假设表名是 schools
        $query->order('ranking', 'asc'); // 按 rank 从小到大排序

        // 处理 province_name 筛选条件
        if (!empty($selectedProvince)) {
            $query->whereIn('province_name', $selectedProvince);
        }

        // 处理 features 筛选条件，确保 features 包含所有 selectLableNames 中的标签
        if (!empty($selectLableNames)) {
            foreach ($selectLableNames as $label) {
                $query->where('features', 'like', "%{$label}%");
            }
        }
        // 处理 cn_name 的模糊查询
        if (!empty($searchKey)) {
            // 将 searchKey 拆分为单个字符或词组部分，并在每个部分之间添加 %
            // 例如，"河大" 变为 "%河%大%"
            $parts = preg_split('//u', $searchKey, -1, PREG_SPLIT_NO_EMPTY); // 使用正则表达式拆分为单个字符，支持多字节字符集（如中文）
            $likePattern = '%' . implode('%', $parts) . '%';

            $query->where(
                'cn_name',
                'like',
                $likePattern
            );
        }


        // 获取符合条件的总记录数
        $totalQuery = clone $query; // 克隆查询对象，用于获取总记录数
        $total = $totalQuery->count(); // 获取总记录数

        // 获取分页数据
        $result = $query->page($page, $pageSize)->select(); // 分页查询

        // 构造返回的 JSON 格式
        $response = [
            'data' => $result,
            'total' => $total, // 总记录数
            'page' => $page,   // 当前页码
            'pageSize' => $pageSize, // 每页数量
            'totalPages' => ceil($total / $pageSize), // 总页数
            'message' => '请求成功第' . $page . '页，还剩' . ceil(($total / $pageSize) - $page) . '页。',
            'status' => 'success'
        ];

        // 返回 JSON 格式的响应
        return json($response);
    }
    public function getScoreByCode(): Json
    {
        // 获取前端传递的分页参数
        $code = Request::param('code', ''); // 学校代码
        try {
            $query = Db::name('school_score_mapping'); // 假设表名是 schools
            // 查询数据
            $school_code = $query->where('code', $code)->value('school_code');
            $query = Db::name('2024_score'); // 假设表名是 schools
            $data = $query->where('school_code', $school_code)->select();
            // 返回响应数据，包含分页信息
            return json([
                'data' => $data,
                'message' => "请求成功",
                'status' => "success"
            ]);
        } catch (\Exception $e) {
            // 捕获其他类型的异常
            return json([
                'error' => 'Internal error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function getAllMajor3(): Json
    {

        $query = Db::name('2024_yzy_majors')
            ->field('code, name')
            ->where('level', '3');
        $data = $query->select()->toArray();  // 转换为普通数组

        // 重命名 code 为 url, name 为 key
        $data = array_map(function ($item) {
            return [
                'url' => $item['code'],
                'key' => $item['name']
            ];
        }, $data);

        return json([
            'data' => $data,
            'message' => "请求成功",
            'status' => "success"
        ]);
    }
    public function getMajorInfo(): Json
    {
        $code = Request::param('code', '');
        $query = Db::name('2024_yzy_majorinfo')->where('code', $code);
        $data = $query->select();
        return json([
            'data' => $data,
            'message' => "请求成功",
            'status' => "success"
        ]);
    }
    public function getAllMajor(): Json
    {
        // 获取一级学科数据
        $disciplines_level_1 = Db::name('2024_yzy_majors')
            ->field('code, name')
            ->where('level', 1)
            ->select();  // 返回 PHP 数组

        // 获取二级学科数据
        $disciplines_level_2 = Db::name('2024_yzy_majors')
            ->field('code, name, parent_id')
            ->where('level', 2)
            ->select();  // 返回 PHP 数组

        // 获取三级学科数据
        $disciplines_level_3 = Db::name('2024_yzy_majors')
            ->field('code, name, parent_id')
            ->where('level', 3)
            ->select();  // 返回 PHP 数组

        // 构建学科树
        function buildDisciplineTree($level1, $level2, $level3)
        {
            $tree = [];
            $level2_map = []; // 用于快速查找二级学科
            $level3_map = []; // 用于快速查找三级学科

            // 构建二级学科映射
            foreach ($level2 as $item) {
                $level2_map[$item['code']] = $item;
                $level2_map[$item['code']]['children'] = [];
            }

            // 构建三级学科映射
            foreach ($level3 as $item) {
                $level3_map[$item['code']] = $item;
            }

            // 构建一级学科树
            foreach ($level1 as $item) {
                $tree[(int) $item['code']] = $item;
                $tree[(int) $item['code']]['children'] = [];

                // 添加二级学科到一级学科
                foreach ($level2_map as $code => $subItem) {
                    if ($subItem['parent_id'] === $item['code']) {
                        // 添加三级学科到二级学科
                        foreach ($level3_map as $subCode => $subSubItem) {
                            if ($subSubItem['parent_id'] === $subItem['code']) {
                                // 使用 push 添加三级学科到二级学科的 children 数组
                                $subItem['children'][] = $subSubItem;
                            }
                        }

                        // 使用 push 添加二级学科到一级学科的 children 数组
                        $tree[(int) $item['code']]['children'][] = $subItem;
                    }
                }
            }

            return $tree;
        }

        // 使用函数构建树
        $disciplineTree = buildDisciplineTree($disciplines_level_1, $disciplines_level_2, $disciplines_level_3);

        // 返回最终的数据
        return json([
            'data' => $disciplineTree,
            'message' => "请求成功",
            'status' => "success"
        ]);
    }
    public function getAllMajorByQuery(): Json
    {
        $majorName = Request::param('majorName', ''); // 学校代码
        $query = Db::name('2024_yzy_majors');
        // 处理 cn_name 的模糊查询
        if (!empty($majorName)) {
            // 将 searchKey 拆分为单个字符或词组部分，并在每个部分之间添加 %
            // 例如，"河大" 变为 "%河%大%"
            $parts = preg_split('//u', $majorName, -1, PREG_SPLIT_NO_EMPTY); // 使用正则表达式拆分为单个字符，支持多字节字符集（如中文）
            $likePattern = '%' . implode('%', $parts) . '%';
            $query->field('code, name')->where(
                'name',
                'like',
                $likePattern
            )->where('level', '3');
        } else {
            return json([
                'data' => [],
                'message' => "没有此数据",
                'status' => "false"
            ]);
        }
        $data = $query->select()->toArray();
        if ($data) {
            // 转换为普通数组
            return json([
                'data' => $data,
                'message' => "请求成功",
                'status' => "success"
            ]);
        } else {
            return json([
                'data' => [],
                'message' => "没有此数据",
                'status' => "false"
            ]);
        }
    }
    //用户登录方法
    function check(): Json
    {
        $userid = Request::param('userid', '');
        $userpassword = Request::param('userpassword', '');
        $query = Db::name('userdata');
        $data = $query->where('id', $userid)->value('userpassword');

        if ($data == $userpassword) {
            $state = Db::name('userdata')->where('id', $userid)->value('state');
            if ($state == 'true') {
                return json([
                    'data' => false,
                    'message' => "用户被封禁",
                    'status' => false
                ]);
            }
            return json([
                'data' => true,
                'message' => "登陆成功",
                'status' => true
            ]);
        } else {
            return json([
                'data' => false,
                'message' => "账号或密码错误",
                'status' => false
            ]);
        }
    }
    function signup(): Json
    {
        $username = Request::param('username', '');
        $userpassword = Request::param('userpassword', '');
        $sex = Request::param('sex', '');
        $birthday = Request::param('birthday', '');
        $intro = Request::param('intro', '');
        $phone = Request::param('phone', '');
        $score = Request::param('score', 0);
        $query = Db::name('userwaitdata');
        $data = $query->where('id', $phone)->find();
        if ($data) {
            return json([
                'data' => false,
                'message' => "用户已存在",
                'status' => false
            ]);
        }
        $query = Db::name('userwaitdata');
        // 创建 DateTime 对象并设置时区（如果需要的话，这里默认是 UTC 因为字符串中有 Z）
        $input_birthday = date('Y-m-d', strtotime($birthday));

        $upDateData = [
            'id' => $phone,
            'username' => $username,
            'userpassword' => $userpassword,
            'sex' => $sex,
            'birthday' => $input_birthday,
            'intro' => $intro,
            'score' => $score,
        ];
        $state = Db::name('userwaitdata')->insert($upDateData);
        if ($state) {
            return json([
                'data' => true,
                'message' => "注册成功",
                'status' => true
            ]);
        } else {
            return json([
                'data' => false,
                'message' => "注册失败",
                'status' => false
            ]);
        }
    }
    public function getUserInfoById(): Json
    {
        $input_userid = Request::param('userId', '');
        $data = Db::table('userdata')->where('id', $input_userid)->select();
        return json([
            'data' => $data,
            'message' => "请求成功",
            'status' => false
        ]);
    }
    public function userChange(): Json
    {
        $username = Request::param('username', '');
        $userpassword = Request::param('userpassword', '');
        $sex = Request::param('sex', '');
        $birthday = Request::param('birthday', '');
        $intro = Request::param('intro', '');
        $phone = Request::param('phone', '');
        $score = Request::param('score', 0);
        // 创建 DateTime 对象并设置时区（如果需要的话，这里默认是 UTC 因为字符串中有 Z）
        $input_birthday = date('Y-m-d', strtotime($birthday));
        $upDateData = [
            'username' => $username,
            'userpassword' => $userpassword,
            'sex' => $sex,
            'birthday' => $input_birthday,
            'intro' => $intro,
            'score' => $score,
        ];
        $state = Db::name('userdata')->where('id', $phone)->update($upDateData);
        if ($state) {
            return json([
                'data' => true,
                'message' => "更改成功",
                'status' => true
            ]);
        } else {
            return json([
                'data' => false,
                'message' => "更改失败",
                'status' => false
            ]);
        }
    }
    public function getScoreByQuery(): Json
    {
        // 获取前端传递的分页参数
        $page = Request::param('page', 1); // 默认为第一页
        $pageSize = Request::param('pageSize', 100); // 默认为每页100条
        $schoolCode = Request::param('school_code', ''); // 学校代码
        $schoolName = Request::param('school_name', ''); // 学校名称
        $majorName = Request::param('major_name', ''); // 专业名称
        $top_ranking = Request::param('top_ranking', 0); // 排名
        $ranking = Request::param('ranking', 999999); // 最低排名

        try {
            // 计算偏移量
            $offset = ($page - 1) * $pageSize;

            // 构建查询
            $query = Db::table("2024_score")
                ->limit($offset, $pageSize);

            // 添加模糊查询条件
            if ($schoolCode) {
                $query->where('school_code', 'like', '%' . $schoolCode . '%');
            }
            if (!empty($schoolName)) {
                // 将 searchKey 拆分为单个字符或词组部分，并在每个部分之间添加 %
                $parts = preg_split('//u', $schoolName, -1, PREG_SPLIT_NO_EMPTY); // 使用正则表达式拆分为单个字符，支持多字节字符集（如中文）
                $likePattern = '%' . implode('%', $parts) . '%';
                $query->where('school_name', 'like', $likePattern);
            }
            // 处理 majorName 的模糊查询
            if (!empty($majorName)) {
                $parts = preg_split('//u', $majorName, -1, PREG_SPLIT_NO_EMPTY); // 使用正则表达式拆分为单个字符，支持多字节字符集（如中文）
                $likePattern = '%' . implode('%', $parts) . '%';
                $query->where('major_name', 'like', $likePattern);
            }

            // 排名条件：如果 ranking 非空且不为 NULL 时，才会添加排名条件
            if (!empty($ranking) && $top_ranking !== 0) {
                $query->where('ranking', '>=', $ranking)
                    ->where('ranking', '<=', $top_ranking);
            }

            // 排除排名为空的记录
            $query->whereNotNull('ranking');

            // 排序：按照 ranking 从大到小排序
            $query->order('ranking', 'esc');

            // 查询数据
            $data = $query->select();

            // 获取总记录数
            $totalCount = Db::table('2024_score')
                ->when($schoolCode, function ($query) use ($schoolCode) {
                    return $query->where('school_code', 'like', '%' . $schoolCode . '%');
                })
                ->when($schoolName, function ($query) use ($schoolName) {
                    return $query->where('school_name', 'like', '%' . $schoolName . '%');
                })
                ->when($majorName, function ($query) use ($majorName) {
                    return $query->where('major_name', 'like', '%' . $majorName . '%');
                })
                // 排名条件：只有 ranking 非空时，才会添加排名条件
                ->when(!empty($ranking) && $top_ranking !== 0, function ($query) use ($ranking, $top_ranking) {
                    return $query->where('ranking', '>=', $ranking)
                        ->where('ranking', '<=', $top_ranking);
                })
                // 排除排名为空的记录
                ->whereNotNull('ranking')
                ->count();

            // 返回响应数据，包含分页信息
            return json([
                'data' => $data,
                'total_count' => $totalCount,
                'message' => "请求成功",
                'status' => "success"
            ]);
        } catch (\Exception $e) {
            // 捕获其他类型的异常
            return json([
                'error' => 'Internal error',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function getRankingByScore(): Json
    {
        // 获取前端传递的分页参数
        $score = Request::param('score', 0); // 学校代码
        try {
            $data = Db::name('score_to_ranking')->where('score', $score)->select(); // 假设表名是 schools
            // 查询数据
            // 返回响应数据，包含分页信息
            return json([
                'data' => $data,
                'message' => "请求成功",
                'status' => "success"
            ]);
        } catch (\Exception $e) {
            // 捕获其他类型的异常
            return json([
                'error' => 'Internal error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    // 添加数据项
    public function addUserApp()
    {
        // 获取前端传递的参数
        $user_id = Request::param('user_id', 0);  // 用户ID
        $app_id = Request::param('app_id', 0);    // 应用ID
        $exist = Db::name('user_apps')->where('user_id', $user_id)->where('app_id', $app_id)->find();
        if ($exist) {
            return json([
                'data' => $exist,
                'message' => "已经添加过了",
                'status' => "false"
            ]);
        }
        // 查询该用户下是否有任何记录，找到最大排名
        $maxRanking = Db::name('user_apps')
            ->where('user_id', $user_id)
            ->max('user_ranking');  // 获取最大排名值

        // 如果没有记录（maxRanking 为 null），则设置 user_ranking 为 1
        if ($maxRanking === null) {
            $user_ranking = 1;  // 用户排名从1开始
        } else {
            $user_ranking = $maxRanking + 1;  // 设置为最大排名+1
        }

        // 准备插入数据
        $data = [
            'user_id' => $user_id,
            'app_id' => $app_id,
            'user_ranking' => $user_ranking
        ];

        // 插入数据
        $insertResult = Db::name('user_apps')->insert($data);

        if ($insertResult) {
            return json([
                'data' => $data,
                'message' => "添加成功",
                'status' => "success"
            ]);
        } else {
            return json([
                'data' => null,
                'message' => "添加失败",
                'status' => "false"
            ]);
        }
    }
    function getUserAppsById(): Json
    {
        $user_id = Request::param('user_id', 0);

        // 从 user_apps 表中获取 user_id 对应的记录，并按 user_ranking 排序
        $apps = Db::name('user_apps')
            ->where('user_id', $user_id)
            ->order('user_ranking', 'ASC')
            ->select()
            ->toArray(); // 确保将结果转换为数组

        // 如果没有找到任何记录，则返回空数组和适当的提示
        if (empty($apps)) {
            return json([
                'data' => [],
                'message' => "没有找到该用户的应用信息",
                'status' => "false"
            ]);
        }

        // 提取所有 app_id
        $appIds = array_column($apps, 'app_id');

        // 从 2024_score 表中获取与这些 app_id 对应的记录
        $scores = Db::name('2024_score')
            ->whereIn('id', $appIds)
            ->select()
            ->toArray(); // 确保将结果转换为数组

        // 准备结果数组
        $result = [];
        // 创建一个以 app_id 为键的 scores 索引数组以提高查找效率
        $scoreIndex = [];
        foreach ($scores as $score) {
            $scoreIndex[$score['id']] = $score;
        }

        // 遍历 apps 数组，为每个应用添加分数信息，并扁平化结构
        foreach ($apps as $app) {
            $appScore = $scoreIndex[$app['app_id']] ?? []; // 使用空数组作为默认值，以防未找到分数
            // 合并 app 和 appScore 数组，但排除 appScore 中的 'id' 字段，因为它与 'app_id' 重复
            $flattenedApp = array_merge($app, array_diff_key($appScore, ['id' => '']));
            $result[] = $flattenedApp;
        }

        // 注意：由于您期望的是单个对象的格式，但函数通常返回数组，这里我们假设您想要返回所有匹配的扁平化对象数组
        // 如果您确实只想返回一个对象（例如，第一个匹配项），则可以在此处进行额外的处理
        // 例如：return json($result[0] ?? []); // 如果 $result 不为空，则返回第一个元素，否则返回空数组（或根据需要返回 null 或其他默认值）

        // 但由于函数签名和通常的 API 设计原则，返回数组是更常见的做法
        return json([
            'data' => $result,
            'message' => "查询成功",
            'status' => "success"
        ]);
    }
    public function deleteAppByid(): Json
    {
        $user_id = Request::param('user_id', 0);  // 用户ID
        $app_id = Request::param('app_id', 0);    // 应用ID

        try {
            // 假设表名是 'user_apps'
            $getRanking = Db::name('user_apps')
                ->where('user_id', $user_id)
                ->where('app_id', $app_id)
                ->value('user_ranking');

            if (!$getRanking) {
                // 如果没有找到对应的记录，返回错误信息
                return json([
                    'data' => null,
                    'message' => "没有此数据",
                    'status' => "false"
                ], 404); // 使用404状态码表示未找到资源
            }

            // 先执行删除操作
            Db::name('user_apps')
                ->where('user_id', $user_id)
                ->where('app_id', $app_id)
                ->delete();

            // 然后执行更新操作，将user_ranking大于被删除记录的user_ranking的所有值减一
            Db::name('user_apps')
                ->where('user_id', $user_id)
                ->where('user_ranking', '>', $getRanking)
                ->setDec('user_ranking'); // setDec是ThinkPHP提供的方法，用于字段值自减

            // 返回成功响应
            return json([
                'data' => true,
                'message' => "请求成功",
                'status' => "success"
            ]);
        } catch (\Exception $e) {
            // 捕获其他类型的异常，并返回错误信息
            return json([
                'error' => 'Internal error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function changeOrder(): Json
    {
        $user_id = Request::param('user_id', false);
        $app_id = Request::param('app_id', false);
        $user_ranking = Request::param('user_ranking', 0); // 使用更具描述性的变量名


        try {
            $originalRanking = Db::name('user_apps')
                ->where('user_id', $user_id)
                ->where('app_id', $app_id)
                ->value('user_ranking');

            if (!$originalRanking) {
                return json([
                    'data' => null,
                    'message' => "没有找到此数据",
                    'status' => "false"
                ], 404);
            }
            // 使用ThinkPHP的Db类查询数据库中的最大user_ranking
            $maxRanking = Db::name('user_apps')
                ->where('user_id', $user_id)
                ->max('user_ranking');

            // 初始化new_ranking为原值（假设为$user_ranking）
            $new_ranking = $user_ranking;

            // 判断如果user_ranking大于数据库中的最大user_ranking，则更新new_ranking
            if ($user_ranking > $maxRanking) {
                $new_ranking = $maxRanking;
            }

            // 如果新顺序小于原顺序，则需要将介于两者之间的顺序值加一
            if ($new_ranking < $originalRanking) {
                Db::name('user_apps')
                    ->where('user_id', $user_id)
                    ->where('user_ranking', '>=', $new_ranking)
                    ->where('user_ranking', '<', $originalRanking)
                    ->setInc('user_ranking');
            }
            // 如果新顺序大于原顺序，则需要将大于原顺序且小于新顺序的顺序值减一（但您已经用setDec处理了所有>=原顺序的，这里需要调整）
            // 然而，由于我们已经用setDec处理了所有>=原顺序的，这里我们只需要确保不重复减即可
            // 因此，我们不需要再次执行setDec操作，而是直接更新目标记录的顺序
            else if ($new_ranking > $originalRanking) {
                // 然后执行更新操作，将user_ranking大于被删除记录的user_ranking的所有值减一
                Db::name('user_apps')
                    ->where('user_id', $user_id)
                    ->where('user_ranking', '>', $originalRanking)
                    ->setDec('user_ranking'); // setDec是ThinkPHP提供的方法，用于字段值自减
            }
            // 如果新顺序等于原顺序，则不需要进行任何操作

            // 更新目标记录的顺序（注意：这里应该使用新顺序值，但由于前面的逻辑已经调整了数据库状态，所以直接使用$new_ranking是安全的）
            Db::name('user_apps')
                ->where('user_id', $user_id)
                ->where('app_id', $app_id)
                ->update(['user_ranking' => $new_ranking]);

            return json([
                'data' => true,
                'message' => "更改成功",
                'status' => "success"
            ]);
        } catch (\Exception $e) {
            return json([
                'error' => 'Internal error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getScoreByCode2022(): Json
    {
        // 获取前端传递的分页参数
        $code = Request::param('code', ''); // 学校代码
        try {
            $query = Db::name('school_score_mapping_2022'); // 假设表名是 schools
            // 查询数据
            $school_code = $query->where('code', $code)->value('school_code');
            $query = Db::name('2022_score'); // 假设表名是 schools
            $data = $query->where('school_code', $school_code)->select();
            // 返回响应数据，包含分页信息
            return json([
                'data' => $data,
                'message' => "请求成功",
                'status' => "success"
            ]);
        } catch (\Exception $e) {
            // 捕获其他类型的异常
            return json([
                'error' => 'Internal error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function getScoreByCode2023(): Json
    {
        // 获取前端传递的分页参数
        $code = Request::param('code', ''); // 学校代码
        try {
            $query = Db::name('school_score_mapping_2023'); // 假设表名是 schools
            // 查询数据
            $school_code = $query->where('code', $code)->value('school_code');
            $query = Db::name('2023_score'); // 假设表名是 schools
            $data = $query->where('school_code', $school_code)->select();
            // 返回响应数据，包含分页信息
            return json([
                'data' => $data,
                'message' => "请求成功",
                'status' => "success"
            ]);
        } catch (\Exception $e) {
            // 捕获其他类型的异常
            return json([
                'error' => 'Internal error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
