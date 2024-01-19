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
    });
});
