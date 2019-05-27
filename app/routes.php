<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/


Route::get('/', 'LoginController@index');
Route::get('/login', 'LoginController@index');

/*
 * Check login process
 */
Route::post('/login/check', 'LoginController@check');
Route::post('/login/load-login-form', 'LoginController@loadLoginForm');
Route::post('/login/load-login-otp-form', 'LoginController@loadLoginOtpForm');
Route::post('/login/otp-login-email-validation-with-token-provide', 'LoginController@otpLoginEmailValidationWithTokenProvide');
Route::post('/login/otp-login-check', 'LoginController@checkOtpLogin');
Route::get('get-routes', 'LoginController@allClassRoute');
Route::post('/login/type_wise_details', 'LoginController@type_wise_details');

/*
 * Google Login routes
 */
Route::get('auth/google', 'GoogleLoginController@redirectToProvider');
Route::get('auth/google/callback', 'GoogleLoginController@handleProviderCallback');

Route::get('oauth/google/callback', 'GoogleLoginController@handleProviderCallback');

/*
 /*
 * Google Login routes
 */
Route::get('auth/facebook', 'FacebookLoginController@redirectToProvider');
Route::get('auth/facebook/callback', 'FacebookLoginController@handleProviderCallback');

/*
 *
 * For language changes
 */
Route::get('language/{lan}', function ($lang) {
    App::setLocale($lang);
    Session::put('lang', $lang);
    \App\Modules\Users\Models\UsersModel::setLanguage($lang);
    return redirect()->back();
});



Route::get('reyad/outside/{lan}', function ($lang) {

      Session::forget('lang');
    Session::put('lang', $lang);
    App::setLocale($lang);
    return redirect('/');
});

Route::get('re-captcha', 'LoginController@reCaptcha');

Route::get('/logout', 'LoginController@logout');



Route::post('/api/new-job', 'ApiController@newJob');
Route::post('/api/action/new-job', 'ApiController@actionNewJob');