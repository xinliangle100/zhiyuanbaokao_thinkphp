<?php

declare(strict_types=1);

namespace app\controller;

use think\facade\Db;
use think\response\Json;
use think\facade\Request;

use think\exception\DbException;

class Management
{

    function getAllAdminData(): Json
    {
        try {        // 查询admin表中的所有数据
            $admins = Db::table(table: 'admindata')->select();

            // 如果你希望对查询结果进行进一步的处理或者格式化
            // 比如按特定格式包装数据
            $wrappedData = [
                'status' => 'success', // 可以返回一个状态码
                'data' => $admins,     // 返回查询结果
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
    function addAdmin(): Json
    {
        try {
            // 获取请求参数
            $id = Request::param('userid');
            $name = Request::param('username');
            $password = Request::param('userpassword');

            // 检查主键id是否已存在
            $exists = Db::name('admindata')->where('id', $id)->find();

            if ($exists) {
                // 如果主键id已存在，可以选择更新数据或返回错误信息
                return json(['status' => 'error', 'message' => 'ID已经存在']);
            } else {
                // 如果主键id不存在，插入新数据
                $result = Db::name('admindata')->insert([
                    'id' => $id,
                    'name' => $name,
                    'password' => $password
                ]);

                if ($result) {
                    return json(['status' => 'success', 'message' => '成功添加']);
                } else {
                    return json(['status' => 'error', 'message' => '添加失败']);
                }
            }
        } catch (\Exception $e) {
            // 异常处理
            return json([
                'status' => 'false',
                'message' => $e->getMessage()
            ]);
        }
    }
    function deleteAdminById(): Json
    {
        $id = Request::param('userid');
        try {

            // 执行删除操作
            $result = Db::table('admindata')->where('id', $id)->delete();

            if ($result) {
                return Json(['status' => 'success', 'message' => '删除成功']);
            } else {
                return Json(['status' => 'error', 'message' => '删除失败']);
            }
        } catch (\Exception $e) {
            // 捕获异常并返回错误信息
            return Json(['status' => 'error', 'message' => '删除操作失败', 'error' => $e->getMessage()]);
        }
    }

    function getAllUserData(): Json
    {
        try {        // 查询admin表中的所有数据
            $admins = Db::table(table: 'userdata')->select();

            // 如果你希望对查询结果进行进一步的处理或者格式化
            // 比如按特定格式包装数据
            $wrappedData = [
                'status' => 'success', // 可以返回一个状态码
                'data' => $admins,     // 返回查询结果
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
    function deleteUserById(): Json
    {
        $id = Request::param('userid');
        try {

            // 执行删除操作
            $result = Db::table('userdata')->where('id', $id)->delete();

            if ($result) {
                return Json(['status' => 'success', 'message' => '删除成功']);
            } else {
                return Json(['status' => 'error', 'message' => '删除失败']);
            }
        } catch (\Exception $e) {
            // 捕获异常并返回错误信息
            return Json(['status' => 'error', 'message' => '删除操作失败', 'error' => $e->getMessage()]);
        }
    }

    function getAllWaitUserData(): Json
    {
        try {        // 查询admin表中的所有数据
            $admins = Db::table(table: 'userwaitdata')->select();

            // 如果你希望对查询结果进行进一步的处理或者格式化
            // 比如按特定格式包装数据
            $wrappedData = [
                'status' => 'success', // 可以返回一个状态码
                'data' => $admins,     // 返回查询结果
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

    public function acceptUser()
    {
        try { // 获取请求数据中的用户ID
            $data = Request::post();
            $id = $data['userid'];

            // 获取指定ID的记录
            $record = Db::table('userwaitdata')->where('id', $id)->find();

            if ($record) {
                // 将记录插入到userdata表中
                $insertResult = Db::table('userdata')->insert($record);

                if ($insertResult) {
                    // 从userwaitdata表中删除该记录
                    $deleteResult = Db::table('userwaitdata')->where('id', $id)->delete();

                    if ($deleteResult) {
                        return json(['status' => true, 'message' => '同意成功']);
                    } else {
                        // 如果删除失败，则删除刚插入的记录
                        Db::table('userdata')->where('id', $id)->delete();
                        return json(['status' => true, 'message' => '删除失败']);
                    }
                } else {
                    return json(['status' => true, 'message' => '插入失败']);
                }
            } else {
                return json(['status' => true, 'message' => '记录不存在']);
            }
        } catch (\Exception $e) {
            // 异常处理
            return json([
                'status' => 'false',
                'message' => $e->getMessage()
            ]);
        }
    }
    function deleteWaitUserById(): Json
    {
        $id = Request::param('userid');
        try {

            // 执行删除操作
            $result = Db::table('userwaitdata')->where('id', $id)->delete();

            if ($result) {
                return Json(['status' => 'success', 'message' => '删除成功']);
            } else {
                return Json(['status' => 'error', 'message' => '删除失败']);
            }
        } catch (\Exception $e) {
            // 捕获异常并返回错误信息
            return Json(['status' => 'error', 'message' => '删除操作失败', 'error' => $e->getMessage()]);
        }
    }

    // 获取指定年份的数据，支持分页
    public function getDataByYear(): Json
    {
        // 获取前端传递的分页参数
        $year = Request::param('year', 1);
        $page = Request::param('page', 1); // 默认为第一页
        $pageSize = Request::param('pageSize', 100); // 默认为每页100条
        $dataBaseName = $year . "_score";
        try {
            // 计算偏移量
            $offset = ($page - 1) * $pageSize;

            // 查询数据
            $data = Db::table($dataBaseName)
                ->limit($offset, $pageSize)
                ->select();

            // 获取总记录数
            $totalCount = Db::table('score')->count();

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
    public function getDataByQuery(): Json
    {
        // 获取前端传递的分页参数
        $year = Request::param('year', 1);
        $page = Request::param('page', 1); // 默认为第一页
        $pageSize = Request::param('pageSize', 100); // 默认为每页100条
        $schoolCode = Request::param('school_code', ''); // 学校代码
        $schoolName = Request::param('school_name', ''); // 学校名称
        $majorName = Request::param('major_name', ''); // 专业名称
        $dataBaseName = $year . "_score";

        try {
            // 计算偏移量
            $offset = ($page - 1) * $pageSize;

            // 构建查询
            $query = Db::table($dataBaseName)
                ->limit($offset, $pageSize);

            // 添加模糊查询条件
            if ($schoolCode) {
                $query->where('school_code', 'like', '%' . $schoolCode . '%');
            }
            // if ($schoolName) {
            //     $query->where('school_name', 'like', '%' . $schoolName . '%');
            // }
            // if ($majorName) {
            //     $query->where('major_name', 'like', '%' . $majorName . '%');
            // }
            // 处理 majorName 的模糊查询
            if (!empty($schoolName)) {
                // 将 searchKey 拆分为单个字符或词组部分，并在每个部分之间添加 %
                // 例如，"河大" 变为 "%河%大%"
                $parts = preg_split('//u', $schoolName, -1, PREG_SPLIT_NO_EMPTY); // 使用正则表达式拆分为单个字符，支持多字节字符集（如中文）
                $likePattern = '%' . implode('%', $parts) . '%';
                $query->where(
                    'school_name',
                    'like',
                    $likePattern
                );
            }
            // 处理 majorName 的模糊查询
            if (!empty($majorName)) {
                // 将 searchKey 拆分为单个字符或词组部分，并在每个部分之间添加 %
                // 例如，"河大" 变为 "%河%大%"
                $parts = preg_split('//u', $majorName, -1, PREG_SPLIT_NO_EMPTY); // 使用正则表达式拆分为单个字符，支持多字节字符集（如中文）
                $likePattern = '%' . implode('%', $parts) . '%';
                $query->where(
                    'major_name',
                    'like',
                    $likePattern
                );
            }

            // 查询数据
            $data = $query->select();

            // 获取总记录数
            $totalCount = Db::table($dataBaseName)
                ->when($schoolCode, function ($query) use ($schoolCode) {
                    return $query->where('school_code', 'like', '%' . $schoolCode . '%');
                })
                ->when($schoolName, function ($query) use ($schoolName) {
                    return $query->where('school_name', 'like', '%' . $schoolName . '%');
                })
                ->when($majorName, function ($query) use ($majorName) {
                    return $query->where('major_name', 'like', '%' . $majorName . '%');
                })
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
    public function changeRecordData(): Json
    {
        // 获取前端传递的参数
        $year = Request::param('year', '1');
        $dataBaseName = $year . "_score";

        $updata = Request::param('updata');
        $school_code = $updata['school_code'];
        $major_code = $updata['major_code'];
        $data = [
            'school_name' => $updata['school_name'],
            'major_name' => $updata['major_name'],
            'min_score' => $updata['min_score'],
            'chinese_score' => $updata['chinese_score'],
            'chinese_max_score' => $updata['chinese_max_score'],
            'foreign_language_score' => $updata['foreign_language_score'],
            'preferred_subject_score' => $updata['preferred_subject_score'],
            'elective_subject_max_score' => $updata['elective_subject_max_score'],
            'elective_subject_second_score' => $updata['elective_subject_second_score'],
            'volunteer_code' => $updata['volunteer_code'],
            'remarks' => $updata['remarks'],
        ];

        // 使用模型保存数据
        try {
            $result = Db::name($dataBaseName)
                ->where('school_code', $school_code)
                ->where('major_code', $major_code)
                ->find();
            if ($result) {
                try {
                    $result = Db::name($dataBaseName)
                        ->where('school_code', $school_code)
                        ->where('major_code', $major_code)
                        ->update($data);
                    return json(['status' => 'success', 'message' => '数据更新成功！']);
                } catch (\Exception $e) {
                    return json(['status' => 'false', 'message' => '发生错误: ' . $e->getMessage()]);
                }
            } else {
                return json(['status' => 'false', 'message' => '数据不存在']);
            }
        } catch (\Exception $e) {
            return json(['status' => 'false', 'message' => '发生错误: ' . $e->getMessage()]);
        }
    }
    function hello(): string
    {
        return "hello";
    }
    function updateMajorData(): Json
    {

        $userdata = Request::param('updateData', '');
        $code = $userdata['code'];
        $updatedata = [
            'big_name' => $userdata['big_name'],
            'middle_name' => $userdata['middle_name'],
            'name' => $userdata['name'],
            'introduction' => $userdata['introduction'],
            'edu_level' => $userdata['edu_level'],
            'learn_year' => $userdata['learn_year'],
            'degree' => $userdata['degree'],
            'male_ratio' => $userdata['male_ratio'],
            'female_ratio' => $userdata['female_ratio'],
            'salary' => $userdata['salary'],
            'edu_objective' => $userdata['edu_objective'],
            'edu_requirement' => $userdata['edu_requirement'],
            'subject_requirement' => $userdata['subject_requirement'],
            'lore_and_ability' => $userdata['lore_and_ability'],
            'study_direction' => $userdata['study_direction'],
            'main_course' => $userdata['main_course'],
            'job_direction' => $userdata['job_direction'],
            'famous_scholar' => $userdata['famous_scholar'],
            'hits' => $userdata['hits'],
        ];


        try {
            $result = Db::name('2024_yzy_majorinfo')
                ->where('code', $code)
                ->update($updatedata);
            if ($result) {
                return json([
                    'data' => true,
                    'message' => "修改成功",
                    'status' => true
                ]);
            } elseif ($result === 0) {
                return json([
                    'data' => true,
                    'message' => "未修改任何数据",
                    'status' => false
                ]);
            } else {
                json(['status' => 'false', 'message' => '发生错误: ']);
            }
        } catch (\Exception $e) {
            return json(['status' => 'false', 'message' => '发生错误: ' . $e->getMessage()]);
        }
    }
    function updateChllageList(): Json
    {
        $updateSchoolList = Request::param('updateSchoolList', '');
        $updateSchoolListCode = $updateSchoolList['code'];
        $updateSchoolListData = [
            'features' => $updateSchoolList['features'],
            'province_name' => $updateSchoolList['province_name'],
            'city_name' => $updateSchoolList['city_name'],
            'categories' => $updateSchoolList['categories'],
            'belong' => $updateSchoolList['belong'],
            'hits' => $updateSchoolList['hits'],
            'ranking' => $updateSchoolList['ranking'],
            'nature_type' => $updateSchoolList['nature_type'],
            'edu_level' => $updateSchoolList['edu_level'],
        ];

        $updateSchoolData = Request::param('updateSchoolData', '');
        $updateSchoolDataCode = $updateSchoolData['code'];
        $updateupdateSchoolData = [
            'created_year' => $updateSchoolData['created_year'],
            'points_of_shuo' => $updateSchoolData['points_of_shuo'],
            'points_of_bo' => $updateSchoolData['points_of_bo'],
            'motto' => $updateSchoolData['motto'],
            'introduction' => $updateSchoolData['introduction'],
        ];

        if (empty($updateSchoolList) || empty($updateSchoolData)) {
            return json(['status' => false, 'message' => '缺少必要的参数']);
        }

        try {
            $result1 = Db::name('2024_yzy_schoollist')
                ->where('code', $updateSchoolListCode)
                ->update($updateSchoolListData);

            $result2 = Db::name('2024_yzy_schoolinfo')
                ->where('code', $updateSchoolDataCode)
                ->update($updateupdateSchoolData);

            if ($result1 && $result2) {
                return json([
                    'data' => true,
                    'message' => "修改成功",
                    'status' => true
                ]);
            } elseif ($result1 === 0 && $result2 === 0) {
                return json([
                    'data' => true,
                    'message' => "未修改任何数据",
                    'status' => false
                ]);
            } else {
                return json([
                    'data' => true,
                    'message' => "修改成功",
                    'status' => false
                ]);
            };
        } catch (\Exception $e) {
            return json(['status' => false, 'message' => '发生错误: ' . $e->getMessage()]);
        }
    }

    function adminLogin(): Json
    {
        $userid = Request::param('username', false);
        $userpassword = Request::param('userpassword', false);
        $query = Db::name('admindata');
        $data = $query->where('id', $userid)->value('password');
        if ($data == $userpassword) {
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
    public function getGongJvXiang(): Json
    {
        $query = Db::name('gong_jv_xiang');
        $data = $query->select();
        return json([
            'data' => $data,
            'message' => "查询成功",
            'status' => 'success'
        ]);
    }
    public function getAllNews(): Json
    {
        $query = Db::name('newspaper');
        $data = $query->select();
        return json([
            'data' => $data,
            'message' => "查询成功",
            'status' => 'success'
        ]);
    }
    public function getAllGuangGao(): Json
    {
        $query = Db::name('guang_gao');
        $data = $query->select();
        return json([
            'data' => $data,
            'message' => "查询成功",
            'status' => 'success'
        ]);
    }
    public function postAll(): Json
    {
        $guangGao = Request::param('guangGao', []);
        $news = Request::param('news', []);
        $gongJvXiang = Request::param('gongJvXiang', []);

        // 如果 'gongJvXiang' 参数存在，开始处理
        if ($gongJvXiang) {
            foreach ($gongJvXiang as $item) {
                // 使用 Db::name('gong_jv_xiang')->save() 根据 'name' 更新数据
                Db::name('gong_jv_xiang')->where('name', $item["name"])->update([
                    'paper_url' => $item['paper_url'],
                    'answer_url' => $item['answer_url']
                ]);  // 使用 'name' 字段作为条件更新
            }
        }
        // 如果 'gongJvXiang' 参数存在，开始处理
        if ($guangGao) {
            foreach ($guangGao as $item) {
                // 使用 Db::name('gong_jv_xiang')->save() 根据 'name' 更新数据
                Db::name('guang_gao')->where('id', $item['id'])->update([
                    'url' => $item['url']
                ]);  // 使用 'name' 字段作为条件更新
            }
        }
        // 如果 'gongJvXiang' 参数存在，开始处理
        if ($news) {
            foreach ($news as $item) {
                // 使用 Db::name('gong_jv_xiang')->save() 根据 'name' 更新数据
                Db::name('newspaper')->where('id', $item['id'])->update([
                    'main_info' => $item['main_info'],
                    'title' => $item['title'],
                    'url' => $item['url']
                ]);  // 使用 'name' 字段作为条件更新
            }
        }


        return json([
            'data' => '',
            'message' => "提交成功",
            'status' => 'success'
        ]);
    }
    public function changeState(): Json
    {
        $userid = Request::param('userid', '');
        $state = Db::name('userdata')->where('id', $userid)->value('state');
        $upDateData = [];
        if ($state == 'false') {
            $upDateData = [
                'state' => 'true',
            ];
        } else {
            $upDateData = [
                'state' => 'false',
            ];
        }
        $state = Db::name('userdata')->where('id', $userid)->update($upDateData);

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
}
