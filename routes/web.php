<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

use App\Http\Controllers\ArticleController;

Route::group([
    'middleware' => 'auth',
], function ($router) {
    Route::post('/article/vote', 'ArticleController@vote');
    Route::resource('article.comment', 'CommentController');

    Route::post('/category/update-logo', 'CategoryController@updateLogo');

    Route::post('/user/update-avatar', 'UserController@updateAvatar');
    Route::get('/home', 'HomeController@index') -> name('home');

    Route::get('/favorite/show-add-article-to-favorite', 'FavoriteController@showAddArticleToFavorite') -> name('show-add-article-to-favorite');
    Route::post('/favorite/add-article-to-favorite', 'FavoriteController@addArticleToFavorite') -> name('add-article-to-favorite');
    Route::resource('/favorite', 'FavoriteController');
});

Route::get('/', 'IndexController@index') -> name('index');
Route::get('/test', 'TestController@index');


/********************* Auth Routes *********************/
Auth::routes();

/********************* End *********************/

//Route::get('/article/search', 'ArticleController@search');
//Route::get('/article/reading', 'ArticleController@reading');
Route::resource('/article', ArticleController::class);

//Route::get('/{metaType}/{id}', 'ArticleController@index')->where('metaType','(category,tag)');

//Route::get('/category/default', 'CategoryController@show')->name('index');
Route::get('/category/{id}', ArticleController::class . '@index')->name('category.show');
Route::get('/tag/{id}', ArticleController::class . '@index')->name('tag.show');
//Route::resource('/tag', 'TagController');

