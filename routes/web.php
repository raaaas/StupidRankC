<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/',function(){
echo 'imworking';
});
Route::get('/{key}', 'Controller@getKeywordSuggestionsFromGoogle');
route::get('/dif/{key}','Controller@keyworddiff');
Route::get('/rank/{key}','Controller@sitestat');
Route::Post('/dif/index','Controller@keyworddiffpost');