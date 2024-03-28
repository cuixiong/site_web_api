<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Common\SendEmailController;
use App\Http\Controllers\Controller;
use App\Models\Authority;
use App\Models\Comment;
use App\Models\ContactUs;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Partner;
use App\Models\Problem;
use App\Models\Qualification;
use App\Models\TeamMember;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * 获取单页面内容
     */
    public function Get(Request $request){
        $link = $request->link ?? 'index';
        if(empty($link)){
            ReturnJson(false,'link is empty');
        }
        $domian = env('APP_URL','');
        $front_menu_id = Menu::where('link',$link)->value('id');
        $content = Page::where('page_id',$front_menu_id)->value('content');
        // $content = str_replace('src="', 'src="'.$domian, $content);
        $content = str_replace('srcset="', 'srcset="'.$domian, $content);
        $content = str_replace('url("', 'url("'.$domian, $content);
        if(strpos($content,'%s')!==false){
            $year = bcsub(date('Y'), 2022); // 两个任意精度数字的减法
            $content = str_replace('%s', bcadd(15, $year), $content);
        }

        if($link=='about'){ // 其中的单页【公司简介】（link值是about）比较特殊：后台此单页富文本编辑器的内容要返回a和b两部分给前端，a和b中间嵌入其它内容。
            $divisionArray = explode('<div id="division"></div>',$content);
            $special = [
                'a' => $divisionArray[0],
                'b' => isset($divisionArray[1]) ? $divisionArray[1] : '',
            ];
            $content = $special;
        }
        ReturnJson(true,'',$content);
    }

    /**
     * 权威引用列表
     */
    public function Quotes(Request $request)
    {
        $page = $request->page ?? 1;
        $pageSize = $request->pageSize ?? 16;
        $category_id = $request->category_id ?? 0;
        $model = Authority::select(['id', 'name as title', 'thumbnail as img','category_id'])->orderBy('sort','asc');
        if($category_id){
            $model = $model->where('class_id',$category_id);
        }
        $count = $model->count();
        $result = $model->offset(($page-1)*$pageSize)->limit($pageSize)->get()->toArray();
        $data = [
            'result' => $result,
            'page' => $page,
            'pageSize' => $pageSize,
            'pageCount' => ceil($count/$pageSize),
            'count' => intval($count),
        ];
        ReturnJson(true,'',$data);
    }

    /**
     * 权威引用单个查询
     */
    public function Quote(Request $request)
    {
        $id = $request->id;
        if(empty($id)){
            ReturnJson(false,'id is empty');
        }
        $data = Authority::select(['name as title', 'description as content'])->where('id',$id)->first();
        ReturnJson(true,'',$data);
    }

    /**
     * 联系我们（表单）
     * @param string $username 姓名
     * @param string $email 邮箱地址
     * @param integer $province_id 省份编号
     * @param integer $city_id 城市编号
     * @param string $phone 电话号码
     * @param string $company 公司名称
     * @param integer $plan_buy_time 计划购买时间
     * @param string $content 留言反馈
     */
    public function ContactUs(Request $request)
    {
        $params = $request->all();
        $name = $params['name'];
        $email = $params['email'];
        $province_id = $params['province_id'];
        $city_id = $params['city_id'];
        $phone = $params['phone'];
        $company = $params['company'];
        $plan_buy_time = $params['plan_buy_time'];
        $content = $params['content'];

        $model = new ContactUs();
        $model->name = $name;
        $model->email = $email;
        $model->area_id = $province_id;
        $model->city_id = $city_id;
        $model->phone = $phone;
        $model->company = $company;
        $model->buy_time = $plan_buy_time;
        $model->remarks = $content;
        $model->status = 0;
        if($model->save()){
            (new SendEmailController)->contactUs($model->id);// 发送邮件
            ReturnJson(true,'',$model);
        } else {
            ReturnJson(false,$model->getModelError());
        }
    }

    /**
     * 定制报告（表单）
     * @param string $username 姓名
     * @param string $email 邮箱地址
     * @param integer $province_id 省份编号
     * @param integer $city_id 城市编号
     * @param string $phone 电话号码
     * @param string $company 公司名称
     * @param integer $plan_buy_time 计划购买时间
     * @param string $content 留言反馈
     */
    public function CustomReports(Request $request)
    {
        $params = $request->all();
        $name = $params['name'];
        $email = $params['email'];
        $province_id = $params['province_id'];
        $city_id = $params['city_id'];
        $phone = $params['phone'];
        $company = $params['company'];
        $plan_buy_time = $params['plan_buy_time'];
        $content = $params['content'];

        $model = new ContactUs();
        $model->name = $name;
        $model->email = $email;
        $model->area_id = $province_id;
        $model->city_id = $city_id;
        $model->phone = $phone;
        $model->company = $company;
        $model->buy_time = $plan_buy_time;
        $model->remarks = $content;
        $model->status = 0;
        if($model->save()){
            // $user = new User();
            // Contact::sendContactEmail($params, $user);// 发送邮件
            (new SendEmailController)->customized($model->id);
            ReturnJson(true,'',$model);
        } else {
            ReturnJson(false,$model->getModelError());
        }
    }


    /**
     * 团队成员
     */
    public function TeamMember(Request $request)
    {
        $data = TeamMember::select([
            'name',
            'position as post',
            'image as img',
            'custom as sketch'
        ])
        ->where('status',1)
        ->orderBy('sort','asc')
        ->get()
        ->toArray();
        ReturnJson(true,'',$data);
    }
    
    /**
     * 资质认证
     */
    public function Qualification(Request $request)
    {
        $params = $request->all();
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? 12;

        $result = Qualification::select([
            'id', 
            'name as title', 
            'thumbnail as thumb', 
            'image as img'
        ])
        ->orderBy('sort','asc')
        ->where('status',1)
        ->offset(($page-1)*$pageSize)
        ->limit($pageSize)
        ->get()
        ->toArray();
        $count = Qualification::where('status',1)->count();

        $data = [
            'result' => $result,
            'page' => $page,
            'pageSize' => $pageSize,
            'pageCount' => ceil($count/$pageSize),
            'count' => intval($count),
        ];
        ReturnJson(true,'',$data);
    }

    /**
     * 常见问题
     */
    public function Faqs()
    {
        $data = Problem::select(['problem as question', 'reply as answer'])->where('status',1)->orderBy('sort','asc')->get()->toArray();
        ReturnJson(true,'',$data);
    }

    /**
     * 客户评价-列表
     */
    public function CustomerEvaluations(Request $request)
    {
        $params = $request->all();
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? 16;

        $query = Comment::select([
            'id',
            'title',
            'image as thumb',
        ])
        ->orderBy('sort','asc')
        ->where('status',1);
        
        $count = $query->count();

        $result = $query->offset(($page-1)*$pageSize)->limit($pageSize)->get()->toArray();

        $data = [
            'result' => $result,
            "page" => $page,
            "pageSize" => $pageSize,
            'pageCount' => ceil($count/$pageSize),
            "count" => intval($count),
        ];
        ReturnJson(true,'',$data);
    }

    /**
     * 客户评价-详情
     */
    public function CustomerEvaluation(Request $request)
    {
        $id = $request->id;
        if(!isset($id)){
            ReturnJson(false, 'id is empty');
        }
        $data = Comment::select([
            'title',
            'created_at',
            'image as img',
        ])
        ->where('status',1)
        ->where('id',$id)
        ->first();
        ReturnJson(true,'',$data);
    }
}