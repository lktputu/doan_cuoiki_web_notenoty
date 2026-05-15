<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/home';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    // ========================================================
    // BẢO VỆ CỔNG: CHẶN TÀI KHOẢN CHƯA KÍCH HOẠT (TIÊU CHÍ 2)
    // ========================================================
    protected function authenticated(Request $request, $user)
    {
        // Nếu cột is_active trong Database vẫn bằng 0 (Chưa click link trong mail)
        if ($user->is_active == 0) {
            
            Auth::logout(); // Đá văng tài khoản ra ngoài ngay lập tức
            
            // Trả về trang đăng nhập kèm theo dòng chữ cảnh báo màu đỏ
            return redirect('/login')->with('warning', 'Tài khoản của bạn chưa được kích hoạt! Vui lòng kiểm tra Email để lấy link.');
        }
    }
}