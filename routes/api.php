<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::middleware('api')->group(function () {
    // Common 控制器
    Route::prefix('common')->group(function () {
        Route::get('top-menus', [\App\Http\Controllers\Common\CommonController::class, 'TopMenus'])->name('顶部导航栏');
        Route::get('info', [\App\Http\Controllers\Common\CommonController::class, 'info'])->name('SEO信息');
        Route::get('bottom-menus', [\App\Http\Controllers\Common\CommonController::class, 'BottomMenus'])->name('底部导航');
        Route::get('control-page', [\App\Http\Controllers\Common\CommonController::class, 'ControlPage'])->name('页面JS操作设置');
        Route::get('link', [\App\Http\Controllers\Common\CommonController::class, 'Link'])->name('友情链接');
        Route::get('purchase-process', [\App\Http\Controllers\Common\CommonController::class, 'PurchaseProcess'])->name('购买流程');
        Route::get('product-tag', [\App\Http\Controllers\Common\CommonController::class, 'ProductTag'])->name('产品标签');
        Route::get('test-xunsearc', [\App\Http\Controllers\Common\CommonController::class, 'TestXunSearch'])->name('讯搜测速接口');
    });

    // index控制器(首页)
    Route::prefix('index')->group(function () {
        Route::get('news-product', [\App\Http\Controllers\IndexController::class, 'NewsProduct'])->name('最新报告');
        Route::get('recommend-product', [\App\Http\Controllers\IndexController::class, 'RecommendProduct'])->name('推荐报告');
        Route::get('recommend-news', [\App\Http\Controllers\IndexController::class, 'RecommendNews'])->name('行业新闻');
        Route::get('partners', [\App\Http\Controllers\IndexController::class, 'partners'])->name('合作伙伴');
        Route::get('office', [\App\Http\Controllers\IndexController::class, 'office'])->name('办公室');
    });

    // Plate控制器(页面板块)
    Route::prefix('plate')->group(function () {
        Route::get('plate-value', [\App\Http\Controllers\PlateController::class, 'PlateValue'])->name('页面板块子级的值');
        Route::get('form', [\App\Http\Controllers\PlateController::class, 'form'])->name('页面板块（包含父级和子级）');
    });

    // Product控制器(报告)
    Route::prefix('product')->group(function () {
        Route::get('list', [\App\Http\Controllers\ProductController::class, 'List'])->name('报告列表');
        Route::get('description', [\App\Http\Controllers\ProductController::class, 'Description'])->name('报告详情');
        Route::get('relevant', [\App\Http\Controllers\ProductController::class, 'Relevant'])->name('相关报告');
        Route::get('news', [\App\Http\Controllers\ProductController::class, 'News'])->name('更多资讯');
        Route::get('filters', [\App\Http\Controllers\ProductController::class, 'Filters'])->name('筛选条件');
    });

    // ContactUs控制器(联系我们)
    Route::prefix('contact-us')->group(function () {
        Route::post('add', [\App\Http\Controllers\ContactUsController::class, 'Add'])->name('新增数据');
        Route::get('dictionary', [\App\Http\Controllers\ContactUsController::class, 'Dictionary'])->name('字典数据');
    });

    // System控制器(网站设置)
    Route::prefix('system')->group(function () {
        Route::get('get-children', [\App\Http\Controllers\SystemController::class, 'GetChildren'])->name('通过父级ID获取子级数据列表');
    });

    // System控制器(网站设置)
    Route::prefix('page')->group(function () {
        Route::get('get', [\App\Http\Controllers\PageController::class, 'Get'])->name('单页面内容');
    });

    // User控制器(用户模块)
    Route::post('login', [\App\Http\Controllers\UserController::class, 'Login'])->name('账号登陆');
    Route::post('register', [\App\Http\Controllers\UserController::class, 'Register'])->name('账号注册');
    Route::post('reset-register-email', [\App\Http\Controllers\UserController::class, 'ResetPasswordEmail'])->name('忘记密码:发送邮箱');
    Route::post('do-reset-register', [\App\Http\Controllers\UserController::class, 'DoResetPassword'])->name('忘记密码:修改密码');
    Route::post('check-email', [\App\Http\Controllers\UserController::class, 'CheckEmail'])->name('验证邮箱');
});
