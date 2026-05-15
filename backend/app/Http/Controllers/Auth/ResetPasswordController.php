<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use App\User;

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    protected $redirectTo = '/home';

    public function __construct()
    {
        $this->middleware('guest');
    }

    // =======================================================
    // 1. TỰ ĐỘNG ĐIỀN EMAIL TỪ BỘ NHỚ (SESSION)
    // =======================================================
    public function showResetForm(Request $request, $token = null)
    {
        // Ưu tiên lấy email từ Session, nếu không có mới để trống
        $email = session('reset_email') ? session('reset_email') : $request->email;
        
        return view('auth.passwords.reset')->with(
            ['token' => $token, 'email' => $email]
        );
    }

    // =======================================================
    // 2. KIỂM TRA BẢO MẬT: CHẶN DÙNG LẠI MẬT KHẨU CŨ
    // =======================================================
    public function reset(Request $request)
    {
        $this->validate($request, $this->rules(), $this->validationErrorMessages());

        // Lấy thông tin người dùng từ Database
        $user = User::where('email', $request->email)->first();

        // Kiểm tra xem mật khẩu mới gõ vào CÓ TRÙNG với mật khẩu đang dùng hay không
        if ($user && Hash::check($request->password, $user->password)) {
            return redirect()->back()
                ->withInput($request->only('email'))
                ->withErrors(['password' => 'Mật khẩu mới không được trùng với mật khẩu hiện tại! Vui lòng chọn mật khẩu khác.']);
        }

        // Nếu qua ải kiểm tra, cho phép đổi mật khẩu
        $response = $this->broker()->reset(
            $this->credentials($request), function ($user, $password) {
                $this->resetPassword($user, $password);
            }
        );

        return $response == Password::PASSWORD_RESET
            ? $this->sendResetResponse($response)
            : $this->sendResetFailedResponse($request, $response);
    }
}