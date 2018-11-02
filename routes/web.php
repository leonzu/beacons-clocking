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

Route::get('post', function(Request $request) {

  $client = new \GuzzleHttp\Client();
  $response = $client->post('httpbin.org/post', [
    GuzzleHttp\RequestOptions::JSON => ['name' => 'Borcea']
]);
echo '<pre>' . var_export($response->getStatusCode(), true) . '</pre>';
echo '<pre>' . var_export($response->getBody()->getContents(), true) . '</pre>';


});



Route::get('/', function () {
    return view('welcome');
});
