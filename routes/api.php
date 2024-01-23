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
    });

    // Product控制器(报告)
    Route::prefix('product')->group(function () {
        Route::get('list', [\App\Http\Controllers\ProductController::class, 'list'])->name('报告列表');;
    });
});
