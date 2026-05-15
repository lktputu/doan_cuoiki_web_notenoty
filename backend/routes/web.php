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

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', 'HomeController@index');

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
Route::post('/notes/store', 'NoteController@store')->name('notes.store');
Route::delete('/notes/destroy/{id}', 'NoteController@destroy')->name('notes.destroy');
Route::put('/notes/update/{id}', 'NoteController@update')->name('notes.update');
Route::post('/notes/pin/{id}', 'NoteController@togglePin')->name('notes.pin');
// Route cho quản lý nhãn
Route::post('/labels', 'LabelController@store')->name('labels.store');
Route::put('/labels/{id}', 'LabelController@update')->name('labels.update');
Route::delete('/labels/{id}', 'LabelController@destroy')->name('labels.destroy');
// Đường dẫn bảo mật (Tiêu chí 21)
Route::post('/notes/set-password/{id}', 'NoteController@setPassword')->name('notes.setPassword');
Route::post('/notes/unlock/{id}', 'NoteController@unlock')->name('notes.unlock');
// Đường dẫn mới để chủ động Khóa lại
Route::post('/notes/lock/{id}', 'NoteController@lock')->name('notes.lock');
// Chia sẻ ghi chú (Tiêu chí 23)
Route::post('/notes/share/{id}', 'NoteController@share')->name('notes.share');
// Lấy dữ liệu Real-time (Tiêu chí 24)
Route::get('/notes/data/{id}', 'NoteController@getNoteData')->name('notes.data');
// Xóa ghi chú khỏi danh sách được chia sẻ
Route::post('/notes/remove-shared/{id}', 'NoteController@removeShared')->name('notes.removeShared');

// ==========================================
// XỬ LÝ LINK KÍCH HOẠT TỪ EMAIL (TIÊU CHÍ 2)
// ==========================================
Route::get('/activate/{token}', function($token) {
    // Tìm người dùng có chứa đoạn mã bí mật giống hệt trên đường link
    $user = \App\User::where('activation_token', $token)->first();
    
    if($user) {
        $user->is_active = 1; // Mở khóa tài khoản
        $user->activation_token = null; // Hủy mã token để không dùng lại được nữa
        $user->save();
        
        return redirect('/login')->with('success', 'Kích hoạt tài khoản thành công! Mời bạn đăng nhập.');
    }
    
    return redirect('/login')->with('warning', 'Đường dẫn kích hoạt không hợp lệ hoặc đã được sử dụng!');
});

// ==========================================
// XEM HỒ SƠ CÁ NHÂN (TIÊU CHÍ 5)
// ==========================================
Route::get('/profile', 'HomeController@profile')->name('profile');

// ==========================================
// CẬP NHẬT HỒ SƠ & AVATAR (TIÊU CHÍ 6)
// ==========================================
Route::post('/profile/update', 'HomeController@updateProfile')->name('profile.update');

// ==========================================
// ĐỔI MẬT KHẨU TỪ TRANG HỒ SƠ (TIÊU CHÍ 7)
// ==========================================
Route::post('/profile/change-password', 'HomeController@changePassword')->name('profile.change-password');

// ==========================================
// CÀI ĐẶT CÁ NHÂN (TIÊU CHÍ 8)
// ==========================================
Route::post('/profile/preferences', 'HomeController@updatePreferences')->name('profile.preferences');
// ==========================================
// TRANG NGOẠI TUYẾN (OFFLINE - TIÊU CHÍ 27)
// ==========================================
Route::get('/offline', function () {
    return view('offline');
});
