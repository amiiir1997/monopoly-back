<?php

use Illuminate\Support\Facades\Route;
use App\Events\MyEvent;

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
Route::Post('/creategame','Controller@creategame');
Route::Post('/joingame','Controller@joingame');
Route::Post('/enterws','Controller@enterws');
Route::Post('/startgame','Controller@startgame');
Route::Post('/initialdata','Controller@initialdata');
Route::Post('/sendmassage','Controller@sendmassage');
Route::Post('/domove','Controller@domove');
Route::Post('/roll','Controller@roll');
Route::Post('/dealsuggest','Controller@dealsuggest');
route::get('/test','Controller@test');

