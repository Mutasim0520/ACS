<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->post('/api/login',[
    'uses' => 'IndexController@login'
]);

///get data API
    $router->get('/api/suppliers',['uses' => 'GetController@getSuppliers']);
    $router->get('/api/categories',['uses' => 'GetController@getCategories']);
    $router->get('/api/products',['uses' => 'GetController@getProducts']);
    $router->get('/api/colors',['uses' => 'GetController@getColors']);
    $router->get('/api/sizes',['uses' => 'GetController@getSizes']);
    $router->get('/api/buyers',['uses' => 'GetController@getBuyers']);
    $router->get('/api/all/purchases',['uses' => 'GetController@getAllPurchases']);
    $router->get('/api/incomplete/purchases',['uses' => 'GetController@getIncompletePurchases']);
    $router->get('/api/due/purchases',['uses' => 'GetController@getDuePurchase']);
    $router->get('/api/paid/purchases',['uses' => 'GetController@getFullPaidPurchase']);
    $router->get('/api/complete/purchases',['uses' => 'GetController@getCompletePurchases']);
    $router->get('/api/extended/purchases',['uses' => 'GetController@getExtendedPurchase']);
    $router->get('/api/all/sales',['uses' => 'GetController@getAllSales']);
    $router->get('/api/incomplete/sales',['uses' => 'GetController@getIncompleteSales']);
    $router->get('/api/complete/sales',['uses' => 'GetController@getCompleteSales']);
    $router->get('/api/due/sales',['uses' => 'GetController@getDueSale']);
    $router->get('/api/paid/sales',['uses' => 'GetController@getFullPaidSale']);
    $router->get('/api/extended/sales',['uses' => 'GetController@getExtendedSale']);
    $router->get('/api/check/duplicate/{item}/{value}',['uses' =>'GetController@checkDuplicate']);
    $router->get('/api/available/products',['uses' =>'GetController@getAvailableProducts']);
    $router->get('/api/indivisual/product',['uses' =>'GetController@getIndivisualProduct']);
    $router->get('/api/ledger/groups',['uses' =>'GetController@getLedgerCategories']);
    $router->get('/api/ledgers/list',['uses' =>'GetController@getLedgerList']);
    $router->get('/api/{role}/purchaseWiseReport',['uses' => 'GetController@getPurchaseWiseReport']);
    $router->get('/api/saleWiseReport',['uses' => 'GetController@getSaleWiseReport']);
    $router->get('/api/{role}/indivisual/purchase',['uses' => 'GetController@getIndivisualPurchase']);
    $router->get('/api/{role}/indivisual/sale',['uses' => 'GetController@getIndivisualSale']);
    $router->get('/api/category/product',['uses'=>'GetController@getCategoryWiseProduct']);
    $router->get('/api/subcategory/product',['uses'=>'GetController@getSubCategoryWiseProduct']);


    $router->get('/api/roles',['uses' => 'GetController@getRoles']);
    $router->get('/api/search',['uses' => 'GetController@search']);


$router->group(['middleware' => 'auth'], function () use ($router){
    $router->post('/api/{role}/supplier/store',['uses' => 'PostController@storeSuppliers']);
    $router->post('/api/{role}/category/store',['uses' => 'PostController@storeCategories']);
    $router->post('/api/{role}/color/store',['uses' => 'PostController@storeColors']);
    $router->post('/api/{role}/size/store',['uses' => 'PostController@storeSizes']);
    $router->post('/api/{role}/buyer/store',['uses' => 'PostController@storeBuyers']);
    $router->post('/api/{role}/subcategory/store',['uses' => 'PostController@storeSubCategories']);
    $router->post('/api/{role}/purchase/product/store',['uses' => 'PostController@storePurchasedProduct']);
    $router->post('/api/{role}/sales/product/store',['uses' => 'PostController@storeSoldProduct']);

    ////Updates Routes

    $router->post('/api/{role}/purchase/product/update',['uses' =>'UpdateController@updatePurchase' ]);
    $router->get('/api/{role}/extended/purchase',['uses' => 'GetController@getExtendedPurchase']);
    $router->get('/api/{role}/due/purchase',['uses' => 'GetController@getDuePurchase']);
    $router->get('/api/{role}/full_paid/purchase',['uses' => 'GetController@getFullPaidPurchase']);


    $router->post('/api/{role}/purchase/price/store',['uses' => 'PostController@storePurchasedProductPrice']);
    $router->post('/api/{role}/purchase/price/update',['uses' => 'UpdateController@updatePurchasedProductPrice']);
    $router->post('/api/{role}/sale/price/store',['uses' => 'PostController@storeSoldProductPrice']);

    $router->get('/api/{role}/journal',['uses' => 'GetController@getJournal']);
    $router->get('/api/{role}/ledgers',['uses' => 'GetController@getLedgers']);
    $router->get('/api/{role}/trailbalances',['uses' => 'GetController@getLedgers']);
    $router->post('/api/{role}/register',['uses' =>'IndexController@register']);
    $router->get('/api/{role}/timeWise/purchases',['uses' => 'GetController@getTimeWisePurchase']);
    $router->get('/api/{role}/timeWise/sales',['uses' => 'GetController@getTimeWiseSale']);
    $router->get('/api/{role}/supplierWise/reports',['uses' => 'GetController@getSupplierWiseReport']);
    $router->get('/api/{role}/buyerWise/reports',['uses' => 'GetController@getBuyerWiseReport']);
    $router->post('/api/{role}/store/ledger',['uses' => 'PostController@storeNewLedger']);
    $router->post('/api/{role}/ledger/add/groups',['uses' =>'PostController@storeLedgerCategories']);
});

$router->group(['middleware' => 'auth'],function () use ($router){
   $router->post('/api/{role}/store/product/details',['uses' => 'PostController@storeProductDetails']);
   $router->post('/api/{role}/add/payment/purchase',['uses' => 'UpdateController@addPaymentToPurchase']);
   $router->post('/api/{role}/add/payment/sale',['uses' => 'UpdateController@addPaymentToSale']);
   $router->post('/api/{role}/update/price/purchase',['uses' => 'UpdateController@updatePricePurchase']);
   $router->post('/api/{role}/update/price/sale',['uses' => 'UpdateController@updatePriceSale']);
   $router->get('/api/{role}/ledgers/bank_accounts',['uses' => 'GetController@getBankAccountsLedgers']);
   $router->get('/api/profit/loss/account',['uses' => 'GetController@getProfitAndLossAccount']);
   $router->get('/api/delete/doc',['uses' => 'PostController@deleteDoc']);
   $router->get('/api/delete/photo',['uses' => 'PostController@deletePhoto']);
   $router->get('/api/purchase/history/accounts',['uses' =>'GetController@getPurchaseHistoryAccounts']);
   $router->get('/api/sale/history/accounts',['uses' =>'GetController@getSaleHistoryAccounts']);
   $router->post('/api/update/sale',['uses' => 'UpdateController@updateSale']);
   $router->get('/api/filter/stock',['uses' => 'GetController@filterStock']);
   $router->get('/api/profit-loss',['uses' => 'GetController@getProfitAndLossAccount']);
   $router->get('/api/admins',['uses' => 'GetController@getAdmins']);
   $router->post('/api/update/admin/{id}',['uses' => 'UpdateController@updateAdmin']);
   $router->delete('/api/delete/admin',['uses' => 'UpdateController@deleteAdmin']);
   $router->get('/api/indivisual/admin',['uses' => 'GetController@getIndivisualAdmin']);
   //new apis
   $router->get('/api/inactivate/user/{id}',['uses' => 'IndexController@inactivateUser']);
   $router->get('/api/activate/user/{id}',['uses' => 'IndexController@activateUser']);
   $router->post('/api/reset/password',['uses' => 'IndexController@resetPassword']);
   $router->post('/api/update/user/role',['uses' => 'IndexController@updateUserRole']);
   $router->post('/api/returns',['uses' => 'PostController@handelReturn']);

});

