<?php

namespace AppBundle\Controller;

use AppBundle\Entity\City;
use AppBundle\Entity\Employee;
use AppBundle\Service\CityService;
use AppBundle\Service\CommentService;
use AppBundle\Service\EmployeeProductService;
use AppBundle\Service\EmployeeService;
use AppBundle\Service\GroupEmployeeService;
use AppBundle\Service\GroupService;
use AppBundle\Service\OrderService;
use AppBundle\Service\ProductService;
use Common\Entity\ServiceTime;
use Common\Service\ServiceTimeService;
use Rain\Request;
use Rain\Application;
use Rain\Auth;
use Rain\Pagination;
use Rain\Redirect;
use Rain\Session;
use Rain\View;
use Rain\UploadedFile;

/**
 * 技师管理控制器
 *
 * @author Zhang Weiwei
 * @since 1.0
 */
class EmployeeController
{
    /**
     * 技师列表
     */
    public function getIndex(Request $request)
    {
        $p = $request->get('page', 1);
        $page = Pagination::createFromCurrentNumber($p);
        $condition = [];
        $params = [];

        $name = $request->get('name', null);
        $sex = $request->get('sex', 0);
        $grade = $request->get('grade', 0);
        $status = $request->get('status', 0);
        if ($name != null) {
            $condition[] = "(name like '%" . trim($name) . "%' or username like '%" . trim($name) . "%')";
        }

         //状态
        if($status == Employee::STATUS_OFF || $status == Employee::STATUS_ON) {
            $condition[] = "status = :status";
            $params[':status'] = $status;
        } else {
           $condition[] = 'status in(' . join(',', [Employee::STATUS_OFF, Employee::STATUS_ON]) . ')';
        }

        //性别
        if($sex == Employee::SEX_MALE || $sex == Employee::SEX_FEMALE) {
            $condition[] = "sex = :sex";
            $params[':sex'] = $sex;
        }

        //星级
         if(isset(Employee::gradeParams()[$grade])) {
            $condition[] = "grade = :grade";
            $params[':grade'] = $grade;
        }

        $list = EmployeeService::findEmployeeList($page, $condition, $params);

        return View::render('@AppBundle/employee/index.twig', [
            'list' => $list,
            'page' => $page,
            'sexArr' => Employee::sexParams(),
            'gradeArr' => Employee::gradeParams(),
            'statusArr' => Employee::statusParams(),
            'totalEmployee' => EmployeeService::totalEmployee(),
            'totalTrade' => EmployeeService::totalTrade()
        ]);
    }

    /**
     * 新增技师
     */
    public function anyCreate(Request $request)
    {
        $errors = [];

        //城市
        $cities = City::getCityList();
        $groups = GroupService::findAllGroupArray();
        if($groups == null) {
             Session::setFlash('message', "请选添加分组，否则技师将没有相关服务");
             return Redirect::to('group/index');
        }

        if ($request->isMethod('post')) {
            $data = $request->get('Employee');

            //头像
            $avatar = Session::pull('avatar');
            $data['avatar'] = $avatar ?: '';

            //位置经纬度
            $coords = explode(',', $data['coords']);
            $data['location_x'] = trim($coords[0]);
            $data['location_y'] = trim($coords[1]);
            unset($data['coords']);

            $newGroup = $data['group_id'];
            unset($data['group_id']);
            if ($lastId = EmployeeService::addEmployee($data, $errors)) {

                //添加技师分组记录
                $tempData = [];
                foreach($newGroup as $key => $val) {
                    $tempData[$key]['employee_id'] = $lastId;
                    $tempData[$key]['group_id'] = $val;
                }
                GroupEmployeeService::addRecord($tempData, $errors);


                Session::setFlash('message', "新增成功");
                return Redirect::to('employee/index');
            }

        }
        return View::render('@AppBundle/employee/create.twig', [
            'errors' => $errors,
            'statusArr' => Employee::statusParams(),
            'gradeArr' => Employee::gradeParams(),
            'sexArr' => Employee::sexParams(),
            'cities' => $cities,
            'groups' => $groups
        ]);

    }

    /**
     * 修改技师
     */
    public function anyUpdate(Request $request, Application $app)
    {
        $errors = [];
        $id = $request->get('id', 0);

        $employee = EmployeeService::findEmployee($id);
        if ($employee == null) {
            $app->abort(404);
        }

        //城市
        $cities = City::getCityList();

        //原来技师所属分组
        $oldGroup = GroupEmployeeService::findAllGroupByEmployee($id);

        if ($request->isMethod('post')) {
            $data = $request->get('Employee');

            //头像
            $avatar = Session::pull('avatar');
            $data['avatar'] = $avatar ?: $employee->avatar;

            //位置经纬度
            $coords = explode(',', $data['coords']);
            $data['location_x'] = trim($coords[0]);
            $data['location_y'] = trim($coords[1]);
            unset($data['coords']);

            $newGroup = isset($data['group_id']) ? $data['group_id'] : [];
            unset($data['group_id']);

            if (EmployeeService::updateEmployee($id, $data, $errors)) {

                 //新增技师所属分组
                $addGroup = [];
                foreach(array_diff($newGroup, $oldGroup) as $k => $v) {
                    $addGroup[$k]['group_id'] = $v;
                    $addGroup[$k]['employee_id'] = $id;
                }
                GroupEmployeeService::addRecord($addGroup, $errors);

                //删除技师所属分组
                $delGroup = [];
                foreach(array_diff($oldGroup, $newGroup) as $k => $v) {
                    $delGroup[$k]['group_id'] = $v;
                    $delGroup[$k]['employee_id'] = $id;
                }
                GroupEmployeeService::deleteRecord($delGroup, $errors);

                Session::setFlash('message', "修改成功");
                return Redirect::to('employee/index');
            }

        }

        return View::render('@AppBundle/employee/update.twig', [
            'errors' => $errors,
            'statusArr' => Employee::statusParams(),
            'gradeArr' => Employee::gradeParams(),
            'sexArr' => Employee::sexParams(),
            'employee' => $employee,
            'cities' => $cities,
            'oldGroup' => $oldGroup,
            'groups' => GroupService::findAllGroupArray()
        ]);

    }

    /**
     * 删除技师
     */
    public function getDelete(Request $request)
    {
        $errors = [];
        $id = $request->get('id', 0);
        $employee = EmployeeService::findEmployee($id);
        if ($employee == null) {
            Session::setFlash('message', "删除失败");
            return Redirect::to('employee/index');
        }

        if (EmployeeService::softDelete($employee, $errors)) {
            Session::setFlash('message', "删除成功");
            return Redirect::to('employee/index');
        }

        Session::setFlash('message', $errors[0]);
        return Redirect::to('employee/index');
    }

    /**
     * 技师详情
     */
    public function getView(Request $request, Application $app)
    {
        $id = $request->get('id', 0);
        $employee = EmployeeService::findEmployee($id);
        if ($employee == null) {
            $app->abort(404);
        }

        return View::render('@AppBundle/employee/view.twig', [
            'employee' => $employee
        ]);

    }

    /**
     * 技师认证资料
     */
    public function getCertificate(Request $request, Application $app)
    {
        $id = $request->get('id', 0);
        $employee = EmployeeService::findEmployee($id);
        if ($employee == null) {
            $app->abort(404);
        }

        return View::render('@AppBundle/employee/certificate.twig', [
            'certificate' => EmployeeService::findCertificateByEmployee($id),
            'employee' => $employee
        ]);
    }

    /**
     * ajax审核通过
     */
    public function anyAjaxPassed(Request $request)
    {
        $result = [
            'status' => 0,
            'message' => ''
        ];
        $id = $request->get('id', 0);
        $certificateId = $request->get('certificate_id', 0);

        $certificate = EmployeeService::findCertificateByEmployee($id);
        if($certificate == null || $certificate->id != $certificateId) {
            $result['message'] = "服务器错误";
            return json_encode($result);
        }
        $error = '';

        if(EmployeeService::updateCertificateStatusPassed($certificateId, $error)) {
            $result['status'] = 1;
        }
        $result['message'] = $error;
         return json_encode($result);

    }

    /**
     * ajax审核不通过
     */
    public function anyAjaxRefuse(Request $request)
    {
        $result = [
            'status' => 0,
            'message' => ''
        ];
        $id = $request->get('id', 0);
        $certificateId = $request->get('certificate_id', 0);

        $certificate = EmployeeService::findCertificateByEmployee($id);
        if($certificate == null || $certificate->id != $certificateId) {
            $result['message'] = "服务器错误";
            return json_encode($result);
        }
        $error = '';

        if(EmployeeService::updateCertificateStatusRefuse($certificateId, $error)) {
            $result['status'] = 1;
        }
        $result['message'] = $error;
         return json_encode($result);

    }

    /**
     * ajax上传图片
     */
    public function anyAjaxUpload()
    {
        $up = new UploadedFile();
        $up->thumb = Employee::getThumbParams();
        $up->basePath = Employee::getImagePath() . '/';
        $up->subPath = @date('Ym') . '/' . @date('d') . '/';
        if ($up->doUpload()) {
            $result = array(
                'status' => 1,
                'files' => $up->getFiles()
            );
            Session::put('avatar', $up->getFiles()[0]['subPath'] . $up->getFiles()[0]['basename']);

        } else {
            $result = array(
                'status' => 0,
                'files' => $up->getError()
            );
        }
        return json_encode($result);

    }

    /**
     * ajax添加城市
     */
    public function anyAjaxAddCity(Request $request)
    {
        //是否有值
        $data['name'] = $request->get('cityName', null);
        if($data['name'] == null){
           $result = [
                'status' => 0,
                'message' => "系统出错",
                'data' => []
            ];
            return json_encode($result);
        }

        //添加城市
        $error = '';
        if($lastId = CityService::addCity($data, $error)){
             $result = [
                'status' => 1,
                'message' => "添加成功",
                'data' => CityService::findCity($lastId)
            ];
        } else {
             $result = [
                'status' => 0,
                'message' => "系统出错",
                'data' => []
            ];
        }
         return json_encode($result);
    }

    /**
     * 技师交易记录
     * @param Request $request
     * @param Application $app
     * @return string
     */
    public function getOrder(Request $request, Application $app)
    {
        $id = $request->get('id', 0);
        $employee = EmployeeService::findEmployee($id);
        if($employee == null) {
            $app->abort(404, "技师id错误");
        }

        $p = $request->get('page', 1);
        $page = Pagination::createFromCurrentNumber($p);
        return View::render('@AppBundle/employee/order.twig', [
            'list' => OrderService::findOrderByEmployeeId($page, $id),
            'page' => $page,
            'employee' => $employee
        ]);
    }

    /**
     * 技师评论记录
     * @param Request $request
     * @param Application $app
     * @return string
     */
    public function getComment(Request $request, Application $app)
    {
        $id = $request->get('id', 0);
        $employee = EmployeeService::findEmployee($id);
        if($employee == null) {
            $app->abort(404, "技师id错误");
        }

        $p = $request->get('page', 1);
        $page = Pagination::createFromCurrentNumber($p);
        return View::render('@AppBundle/employee/comment.twig', [
            'list' => CommentService::findCommentByEmployeeId($page, $id),
            'page' => $page,
            'employee' => $employee
        ]);
    }

    /**
     * 技师预约时间管理
     */
    public function getServiceTime(Request $request, Application $app)
    {
        $id = $request->get('id', 0);
        $employee = EmployeeService::findEmployee($id);
        if($employee == null) {
            $app->abort('404', "技师ID错误");
        }

        //查询技师所有预约时间
        $noServiceArr = ServiceTimeService::findServiceTimeThreeRecord($id);

        //全天时间段
        $timeArr =  [
                '00:00','00:30','01:00', '01:30', '02:00', '02:30', '03:00','03:30', '04:00', '04:30', '05:00',
                '05:30', '06:00', '06:30', '07:00','07:30', '08:00', '08:30', '09:00', '09:30', '10:00', '10:30',
                '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00',
                '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00',
                '19:30', '20:00', '20:30', '21:00', '21:30','22:00', '22:30', '23:00','23:30'
        ];


        return View::render('@AppBundle/employee/service-time.twig', [
            'checkedTime' => static::checkedTime($noServiceArr, $timeArr),
            'timeArr' => $timeArr,
            'employee' => $employee
        ]);


    }

    /**
     * 组装不可预约时间
     * @param $noServiceArr
     * @return array
     */
    protected static function checkedTime($noServiceArr, $timeArr)
    {
        $checkTime = [];
        if(empty($noServiceArr)) {
            return $checkTime;
        }

       //有预约时间
       foreach ($noServiceArr as $val) {
            $temp = [];
            foreach ($timeArr as $k => $v) {

                //技师不可预约时间
                $timeslot = ServiceTime::aheadTime($val->timeslot);
                if (in_array($v, ServiceTime::getTimeForMember($timeslot, $timeArr))) {
                    $temp[] = $timeArr[$k];
                }
            }
            $checkTime[$val->date] = array_unique($temp);
       }

        return $checkTime;

    }

    /**
     * 修改密码
     */
    public function anyResetPassword(Request $request, Application $app)
    {
        $result = [
            'status' => 0,
            'message' => "",
        ];
        $id = $request->get('id', 0);
        $employee = EmployeeService::findEmployee($id);
        if($request->isMethod('get')) {
            if($employee == null) {
                $app->abort(404, "技师id错误");
            }
            return View::render("@AppBundle/employee/reset.twig", [
                'employee' => $employee
            ]);
        }

        $error = '';
        $data['password'] = $request->get('password', $employee->password);
        if(EmployeeService::resetPassword($employee, $data, $error)) {
            $result['status'] = 1;
        }

        $result['message'] = $error;
        return json_encode($result);

    }


}
