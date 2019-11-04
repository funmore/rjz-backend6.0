<?php

use Illuminate\Http\Request;

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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return view('welcome');
});


Route::middleware('auth:api')->get('/user', function (Request $request) {
    return view('welcome');
});
Route::group(['middleware' => ['weixin','log']], function () {
    Route::get('/api/employee/getInfo','Api\UserController@getInfo');


    Route::resource('/programedit', 'Api\ProgramEditController');
    Route::get('/customprogramedit', 'Api\ProgramEditController@custom');
    Route::resource('/pre/program', 'Api\PreProgramEditController');
    Route::resource('/programlog', 'Api\ProgramLogController');
    // Route::get('/pre/program/preshow', 'Api\PreProgramEditController@preshow');

    Route::resource('/employee', 'Api\EmployeeController');
    Route::resource('/softwareinfo', 'Api\SoftwareInfoController');
    Route::resource('/programteamrole', 'Api\ProgramTeamRoleController');
    Route::resource('/programteamroletask', 'Api\ProgramTeamRoleTaskController');
    Route::resource('/program', 'Api\ProgramController');
    Route::get('/program/role/{id}', 'Api\ProgramController@role');
    Route::get('/program/team/{id}', 'Api\ProgramController@team');
    Route::resource('/pvlog', 'Api\PvlogController');
    Route::resource('/workflow', 'Api\WorkflowController');
    Route::resource('/node', 'Api\NodeController');
    Route::resource('/workflownote', 'Api\WorkflowNoteController');
    Route::resource('/dailynote', 'Api\DailyNoteController');
    Route::resource('/delayapply', 'Api\DelayApplyController');
    Route::resource('/nodenote', 'Api\NodeNoteController');
    Route::resource('/contact', 'Api\ContactController');
    Route::resource('/fileprogram', 'Api\FileProgramController');
    Route::resource('/filereview', 'Api\FileReviewController');
    Route::resource('/statistic/people', 'Api\StatisticPeopleController');
    Route::resource('/poll', 'Api\PollController');
    Route::get('/showUnPollPeople/{id}', 'Api\PollController@showUnPollPeople');

    Route::resource('/pollfill', 'Api\PollFillController');
    Route::resource('/model', 'Api\ModelController');
    Route::resource('/BatchImport', 'Api\BatchImportController');
    Route::resource('/notestwork', 'Api\NoTestWorkController');
    Route::get('/notestworklogmonth', 'Api\NoTestWorkController@month');
    Route::resource('/favor', 'Api\FavorController');
    Route::resource('/Team', 'Api\TeamController');


    //workfow end


});

 Route::group(['middleware' => ['wxlogin']], function () {
     Route::get('/api/login', 'Api\UserController@login');
     Route::get('/api/logout', 'Api\UserController@logout');

 });
