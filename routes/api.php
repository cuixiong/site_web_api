<?php

use App\Http\Middleware\LanguageMiddleware;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\JwtMiddleware;

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
Route::middleware(['api', LanguageMiddleware::class])->group(function () {
    // Common 控制器
    Route::prefix('common')->group(function () {
        Route::get('main-data', [\App\Http\Controllers\Common\CommonController::class, 'index'])->name(
            '组件导航主数据接口'
        );
        Route::get('top-menus', [\App\Http\Controllers\Common\CommonController::class, 'TopMenus'])->name('顶部导航栏');
        Route::get('info', [\App\Http\Controllers\Common\CommonController::class, 'info'])->name('SEO信息');
        Route::get('bottom-menus', [\App\Http\Controllers\Common\CommonController::class, 'BottomMenus'])->name(
            '底部导航'
        );
        Route::get('control-page', [\App\Http\Controllers\Common\CommonController::class, 'ControlPage'])->name(
            '页面JS操作设置'
        );
        Route::get('link', [\App\Http\Controllers\Common\CommonController::class, 'Link'])->name('友情链接');
        Route::get('purchase-process', [\App\Http\Controllers\Common\CommonController::class, 'PurchaseProcess'])->name(
            '购买流程'
        );
        Route::get('product-tag', [\App\Http\Controllers\Common\CommonController::class, 'ProductTag'])->name(
            '产品标签'
        );
        Route::get('test-xunsearc', [\App\Http\Controllers\Common\CommonController::class, 'TestXunSearch'])->name(
            '讯搜测速接口'
        );
        Route::get('china-regions', [\App\Http\Controllers\Common\CommonController::class, 'ChinaRegions'])->name(
            '中国地区'
        );
        Route::get('set', [\App\Http\Controllers\Common\CommonController::class, 'Set'])->name('站点设置(分类)');
        Route::get('setting-value', [\App\Http\Controllers\Common\CommonController::class, 'settingValue'])->name(
            '站点设置(单个)'
        );
        Route::get('product-keyword', [\App\Http\Controllers\Common\CommonController::class, 'ProductKeyword'])->name(
            '热搜关键词'
        );
        Route::get('other-website', [\App\Http\Controllers\Common\CommonController::class, 'OtherWebsite'])->name(
            '其它语言网站'
        );
    });
    // index控制器(首页)
    Route::prefix('index')->group(function () {
        Route::get('main-data', [\App\Http\Controllers\IndexController::class, 'index'])->name('首页接口主要数据');
        Route::get('news-product', [\App\Http\Controllers\IndexController::class, 'NewsProduct'])->name('最新报告');
        Route::get('recommend-product', [\App\Http\Controllers\IndexController::class, 'RecommendProduct'])->name(
            '推荐报告'
        );
        Route::get('recommend-news', [\App\Http\Controllers\IndexController::class, 'RecommendNews'])->name('行业新闻');
        Route::get('partners', [\App\Http\Controllers\IndexController::class, 'partners'])->name('合作伙伴');
        Route::get('office', [\App\Http\Controllers\IndexController::class, 'office'])->name('办公室');
        Route::get('customers-comments', [\App\Http\Controllers\IndexController::class, 'customersComments'])->name(
            '客户评价'
        );
        Route::get('quotes', [\App\Http\Controllers\IndexController::class, 'quotes'])->name('权威引用');
        Route::get('qualifications', [\App\Http\Controllers\IndexController::class, 'qualifications'])->name('资质认证');
    });
    // Plate控制器(页面板块)
    Route::prefix('plate')->group(function () {
        Route::get('plate-value', [\App\Http\Controllers\PlateController::class, 'PlateValue'])->name(
            '页面板块子级的值'
        );
        Route::get('plate-value-list', [\App\Http\Controllers\PlateController::class, 'PlateValueList'])->name(
            '页面板块子级的值列表'
        );
        Route::get('plate-value-list-by-link', [\App\Http\Controllers\PlateController::class, 'PlateValueByLink'])
             ->name(
                 '页面板块子级的值列表-通过页面link'
             );
        Route::get('form', [\App\Http\Controllers\PlateController::class, 'Form'])->name('页面板块（包含父级和子级）');
    });
    // Product控制器(报告)
    Route::prefix('product')->group(function () {
        Route::get('list', [\App\Http\Controllers\ProductController::class, 'List'])->name('报告列表');
        Route::any('description', [\App\Http\Controllers\ProductController::class, 'Description'])->name('报告详情');
        Route::get('view-log', [\App\Http\Controllers\ProductController::class, 'viewProductLog'])->name(
            '详情浏览记录'
        );
        Route::get('relevant', [\App\Http\Controllers\ProductController::class, 'Relevant'])->name('相关报告');
        Route::get('news', [\App\Http\Controllers\ProductController::class, 'News'])->name('更多资讯');
        Route::get('filters', [\App\Http\Controllers\ProductController::class, 'Filters'])->name('筛选条件');
        Route::get('output-pdf', [\App\Http\Controllers\ProductController::class, 'OutputPdf'])->name('下载PDF');

        Route::get('customized-info', [\App\Http\Controllers\ProductController::class, 'customizedInfo'])->name('tycn新增接口');
    });
    // ContactUs控制器(联系我们)
    Route::prefix('contact-us')->group(function () {
        Route::post('add', [\App\Http\Controllers\ContactUsController::class, 'Add'])->name('新增数据');
        Route::get('dictionary', [\App\Http\Controllers\ContactUsController::class, 'Dictionary'])->name('字典数据');
        Route::get('company-overview', [\App\Http\Controllers\ContactUsController::class, 'companyOverview'])->name('公司信息');
    });
    // System控制器(网站设置)
    Route::prefix('page')->group(function () {
        Route::get('get', [\App\Http\Controllers\PageController::class, 'Get'])->name('单页面内容');
        Route::get('quotes', [\App\Http\Controllers\PageController::class, 'Quotes'])->name('权威引用列表');
        Route::get('quote', [\App\Http\Controllers\PageController::class, 'Quote'])->name('权威引用');
        Route::get('quote-relevant-product', [\App\Http\Controllers\PageController::class, 'QuoteRelevantProduct'])
             ->name('权威引用相关报告');
        Route::get('team-member', [\App\Http\Controllers\PageController::class, 'TeamMember'])->name('团队成员');
        Route::get('analyst-group', [\App\Http\Controllers\PageController::class, 'AnalystGroup'])->name('分析师团队');
        Route::get('qualification', [\App\Http\Controllers\PageController::class, 'Qualification'])->name('资质认证');
        Route::get('faqs', [\App\Http\Controllers\PageController::class, 'Faqs'])->name('常见问题');
        Route::get('company-history', [\App\Http\Controllers\PageController::class, 'CompanyHistory'])->name('发展历程');
        Route::get('customer-evaluations', [\App\Http\Controllers\PageController::class, 'CustomerEvaluations'])->name(
            '客户评价-列表'
        );
        Route::get('customer-evaluation', [\App\Http\Controllers\PageController::class, 'CustomerEvaluation'])->name(
            '客户评价-详情'
        );
        Route::post('application-sample', [\App\Http\Controllers\PageController::class, 'ApplicationSample'])->name(
            '申请样本'
        );
        Route::post('custom-reports', [\App\Http\Controllers\PageController::class, 'CustomReports'])->name('定制报告');
        Route::post('contact-us', [\App\Http\Controllers\PageController::class, 'ContactUs'])->name('联系我们');
    });
    // User控制器(用户模块)
    Route::post('login', [\App\Http\Controllers\UserController::class, 'Login'])->name('账号登陆');
    Route::post('register', [\App\Http\Controllers\UserController::class, 'Register'])->name('账号注册');
    Route::post('reset-register-email', [\App\Http\Controllers\UserController::class, 'ResetPasswordEmail'])->name(
        '忘记密码:发送邮箱'
    );
    Route::post('do-reset-register', [\App\Http\Controllers\UserController::class, 'DoResetPassword'])->name(
        '忘记密码:修改密码'
    );
    Route::post('send-email-again', [\App\Http\Controllers\UserController::class, 'SendEmailAgain'])->name(
        '再次发送邮件'
    );
    Route::get('check-email', [\App\Http\Controllers\UserController::class, 'CheckEmail'])->name('验证邮箱');
    Route::post('exists-email', [\App\Http\Controllers\UserController::class, 'ExistsEmail'])->name('邮箱是否存在');
    Route::middleware(JwtMiddleware::class)->get('loginout', [\App\Http\Controllers\UserController::class, 'loginout'])
         ->name('退出登陆');
    Route::prefix('user')->group(function () {
        Route::middleware(JwtMiddleware::class)->get('info', [\App\Http\Controllers\UserController::class, 'Info'])
             ->name('Info接口');
        Route::middleware(JwtMiddleware::class)->post(
            'coupons', [\App\Http\Controllers\UserController::class, 'Coupons']
        )->name('查询用户优惠卷');
        Route::post('verify-email', [\App\Http\Controllers\UserController::class, 'VerifyEmail'])->name('注册验证邮箱');
        Route::middleware(JwtMiddleware::class)->post('update', [\App\Http\Controllers\UserController::class, 'update'])
             ->name('用户信息修改');
        Route::middleware(JwtMiddleware::class)->post(
            'change-password', [\App\Http\Controllers\UserController::class, 'changePassword']
        )
             ->name('修改密码');
    });
    //user-address 用户收货地址
    Route::prefix('user-address')->middleware(JwtMiddleware::class)->group(function () {
        Route::get('list', [\App\Http\Controllers\UserAddressController::class, 'list'])->name('收货地址列表');
        Route::post('add', [\App\Http\Controllers\UserAddressController::class, 'store'])->name('添加收货地址');
        Route::get('form/{id}', [\App\Http\Controllers\UserAddressController::class, 'form'])->name('根据id查询');
        Route::post('update', [\App\Http\Controllers\UserAddressController::class, 'update'])->name('修改收货地址');
        Route::get('delete/{id}', [\App\Http\Controllers\UserAddressController::class, 'delete'])->name('收货地址删除');
        Route::get('set-default/{id}', [\App\Http\Controllers\UserAddressController::class, 'setDefault'])->name(
            '设置默认'
        );
    });
    //user-invoices 用户发票
    Route::prefix('user-invoices')->group(function () {
        Route::middleware(JwtMiddleware::class)->get('list', [\App\Http\Controllers\InvoicesController::class, 'list'])
             ->name('发票列表');
        Route::middleware(JwtMiddleware::class)->get(
            'form/{id}', [\App\Http\Controllers\InvoicesController::class, 'form']
        )->name('发票详情');
        Route::middleware(JwtMiddleware::class)->post(
            'apply', [\App\Http\Controllers\InvoicesController::class, 'apply']
        )->name('申请发票');
        Route::post('apply-single-page', [\App\Http\Controllers\InvoicesController::class, 'applySinglePage'])->name(
            '申请发票(单页)'
        );
        Route::post('apply-single-page-old', [\App\Http\Controllers\InvoicesController::class, 'oldApplySinglePage'])->name(
            '申请发票(旧单页)'
        );
    });
    // Cart控制器(购物车模块)
    Route::prefix('cart')->middleware(JwtMiddleware::class)->group(function () {
        Route::post('add', [\App\Http\Controllers\CartController::class, 'Add'])->name('购物车添加');
        Route::post('list', [\App\Http\Controllers\CartController::class, 'List'])->name('购物车列表');
        Route::post('delete', [\App\Http\Controllers\CartController::class, 'Delete'])->name('购物车删除');
        Route::post('updata-goods-number', [\App\Http\Controllers\CartController::class, 'UpdataGoodsNumber'])->name(
            '购物车添加或减少商品数量'
        );
        Route::post('change-edition', [\App\Http\Controllers\CartController::class, 'ChangeEdition'])->name(
            '购物车修改版本'
        );
        Route::post('sync', [\App\Http\Controllers\CartController::class, 'Sync'])
             ->name('购物车同步');
    });
    Route::post('cart/goods-exist', [\App\Http\Controllers\CartController::class, 'goodsExist'])->name('购物车添加');
    Route::get('cart/relevant', [\App\Http\Controllers\CartController::class, 'Relevant'])->name('相关报告');
    Route::post('cart/share', [\App\Http\Controllers\CartController::class, 'Share'])->name('分享购物车数据');
    // Order控制器
    Route::prefix('order')->group(function () {
        Route::middleware(JwtMiddleware::class)->get('list', [\App\Http\Controllers\OrderController::class, 'list'])
             ->name('用户订单列表');
        Route::middleware(JwtMiddleware::class)->get('form/{id}', [\App\Http\Controllers\OrderController::class, 'form']
        )->name('订单详情');
        Route::middleware(JwtMiddleware::class)->get(
            'del/{id}', [\App\Http\Controllers\OrderController::class, 'delete']
        )->name('订单删除');
        Route::middleware(JwtMiddleware::class)->post(
            'change-pay-type', [\App\Http\Controllers\OrderController::class, 'changePayType']
        )
             ->name('更换支付方式');
        Route::middleware(JwtMiddleware::class)->get(
            'pull-pay', [\App\Http\Controllers\OrderController::class, 'pullPay']
        )
             ->name('拉起支付');
        Route::post('coupon', [\App\Http\Controllers\OrderController::class, 'Coupon'])->name('查询优惠卷');
        Route::post('create-and-pay', [\App\Http\Controllers\OrderController::class, 'CreateAndPay'])->name(
            '购物车结算产品列表'
        );
        Route::get('pay', [\App\Http\Controllers\OrderController::class, 'Pay'])->name('订单调用支付');
        Route::get('wechat-order', [\App\Http\Controllers\OrderController::class, 'WechatOrder'])->name('获取CODE信息');
        Route::get('payment', [\App\Http\Controllers\OrderController::class, 'Payment'])->name('支付方式');
        Route::post('details', [\App\Http\Controllers\OrderController::class, 'Details'])->name('检测已支付订单');
    });
    // 支付宝回调
    Route::post('notify/alipay', [\App\Http\Controllers\Pay\Notify::class, 'Alipay'])->name('支付宝回调');
    // 微信支付回调
    Route::post('notify/wechatpay', [\App\Http\Controllers\Pay\Notify::class, 'Wechatpay'])->name('微信支付回调');
    // stripe支付回调
    Route::any('notify/stripe', [\App\Http\Controllers\Pay\Notify::class, 'Stripe'])->name('stripe支付回调');
    // firstData支付回调
    Route::any('notify/firstdata', [\App\Http\Controllers\Pay\Notify::class, 'FirstData'])->name('firstdata支付回调');
    // wise支付回调
    Route::any('notify/wisepay', [\App\Http\Controllers\Pay\Notify::class, 'wiseNotify'])->name('wise支付回调');
    // paypal支付回调
    Route::any('notify/paypal', [\App\Http\Controllers\Pay\Notify::class, 'paypalNotify'])->name('paypal支付回调');
    // airwallex支付回调
    Route::any('notify/airwallex', [\App\Http\Controllers\Pay\Notify::class, 'airwallexNotify'])->name(
        'airwallex支付回调'
    );
    // News控制器
    Route::prefix('news')->group(function () {
        Route::post('index', [\App\Http\Controllers\NewsController::class, 'Index'])->name('新闻列表');
        Route::any('view', [\App\Http\Controllers\NewsController::class, 'View'])->name('新闻详情');
        Route::get('relevant', [\App\Http\Controllers\NewsController::class, 'Relevant'])->name('相关新闻');
        Route::get('relevant-products', [\App\Http\Controllers\NewsController::class, 'RelevantProducts'])->name(
            '相关报告列表'
        );
    });
    // Information控制器 (热门资讯)
    Route::prefix('information')->group(function () {
        Route::post('index', [\App\Http\Controllers\InformationController::class, 'Index'])->name('热门资讯列表');
        Route::any('view', [\App\Http\Controllers\InformationController::class, 'View'])->name('热门资讯详情');
        Route::get('relevant', [\App\Http\Controllers\InformationController::class, 'Relevant'])->name('相关热门资讯');
        Route::get('relevant-products', [\App\Http\Controllers\InformationController::class, 'RelevantProducts'])->name(
            '相关报告列表'
        );
    });
    // caseShare 案例分享
    Route::prefix('case-share')->group(function () {
        Route::get('list', [\App\Http\Controllers\CaseShareController::class, 'list'])->name('案列分享列表');
    });

    // question 问答接口
    Route::prefix('question')->group(function () {
        Route::get('list', [\App\Http\Controllers\QuestionsController::class, 'list'])->name('问答接口列表');
        Route::get('detail', [\App\Http\Controllers\QuestionsController::class, 'detail'])->name('问答接口详情');
        Route::post('answer', [\App\Http\Controllers\QuestionsController::class, 'answer'])->name('回答问题');
    });

    // publisher 控制器
    Route::prefix('publisher')->group(function () {
        Route::get('alphabetic-search', [\App\Http\Controllers\PublisherController::class, 'alphabeticSearch'])->name('新闻列表');
        Route::any('publishers', [\App\Http\Controllers\PublisherController::class, 'publishers'])->name('新闻详情');
        Route::get('search-auto', [\App\Http\Controllers\PublisherController::class, 'searchAuto'])->name('相关新闻');
    });
    // Sitemap(网站地图)控制器
    Route::prefix('sitemap')->group(function () {
        //Route::get('make-site-map', [\App\Http\Controllers\SitemapController::class, 'MakeSiteMap'])->name('更新地图');
    });
    //第三方接口
    Route::prefix('third')->group(function () {
        Route::post('send-email', [\App\Http\Controllers\Third\ThirdRespController::class, 'sendEmail'])->name(
            '发送邮件'
        );
        Route::post('test-send-email', [\App\Http\Controllers\Third\ThirdRespController::class, 'testSendEmail'])->name(
            '测试场景发送邮件'
        );
        Route::post('sync-redis-val', [\App\Http\Controllers\Third\ThirdRespController::class, 'syncRedisVal'])->name(
            '发送邮件'
        );

        Route::post('clear-ban', [\App\Http\Controllers\Third\ThirdRespController::class, 'clearBan'])->name(
            '清除封禁'
        );
    });
    Route::get('get/client-ip', [\App\Http\Controllers\PublicController::class, 'getClientIp'])->name('获取客户端IP');
    Route::get('xunsearch/clean', [\App\Http\Controllers\XunSearchTestController::class, 'clean'])->name(
        '讯搜清空数据'
    );
    Route::get('stripe-paytest', [\App\Http\Controllers\XunSearchTestController::class, 'stripePayTest'])->name(
        '支付测试'
    );
    Route::get('xunsearch/test', [\App\Http\Controllers\XunSearchTestController::class, 'test'])->name('测试接口');
    Route::get('/test1', [\App\Http\Controllers\TestController::class, 'test1'])->name('测试接口1');
    //微信授权
    Route::prefix('wx-empower')->group(function () {
        Route::any('index1', [\App\Http\Controllers\WxAuthController::class, 'getWxAuthCode'])->name(
            '获取微信授权码'
        );
    });
});
