<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    use RegistersUsers;

    protected $redirectTo = '/home';

    public function __construct()
    {
        $this->middleware('guest');
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);
    }

    protected function create(array $data)
    {
        $token = Str::random(40); // Tạo chuỗi ngẫu nhiên 40 ký tự
        $activationLink = url('/activate/' . $token); // Tạo link kích hoạt
        
        // GỬI EMAIL THỰC TẾ
        Mail::send('emails.activation', ['link' => $activationLink], function ($message) use ($data) {
            $message->to($data['email'], $data['name'])
                    ->subject('Vui lòng kích hoạt tài khoản Ghi chú của bạn!');
        });

        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'is_active' => 0, // Mặc định khóa
            'activation_token' => $token
        ]);
    }

    // ========================================================
    // HÀM NÀY SẼ CHẶN ĐỨNG VIỆC LARAVEL TỰ ĐỘNG ĐĂNG NHẬP
    // ========================================================
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();
        
        // Chỉ tạo User và gửi Mail, KHÔNG login
        $this->create($request->all());

        // Đẩy ra trang đăng nhập và báo thông báo màu đỏ
        return redirect('/login')->with('warning', 'Đăng ký thành công! Vui lòng kiểm tra hộp thư đến (hoặc thư rác) để lấy link kích hoạt.');
    }
}