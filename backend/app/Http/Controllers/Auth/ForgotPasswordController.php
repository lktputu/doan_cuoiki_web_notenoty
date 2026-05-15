<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    public function __construct()
    {
        $this->middleware('guest');
    }

    // =======================================================
    // GHI ĐÈ ĐỂ LƯU TẠM EMAIL VÀO BỘ NHỚ (SESSION)
    // =======================================================
    public function sendResetLinkEmail(Request $request)
    {
        $this->validateEmail($request);

        // Lưu email người dùng vừa nhập vào Session
        session(['reset_email' => $request->email]);

        // Gửi link khôi phục (Đã sửa lỗi phiên bản ở đây)
        $response = $this->broker()->sendResetLink(
            $request->only('email')
        );

        return $response == Password::RESET_LINK_SENT
            ? back()->with('status', trans($response))
            : back()->withErrors(['email' => trans($response)]);
    }
}