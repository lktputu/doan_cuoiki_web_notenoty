@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<div class="container">
    <div class="row">
        <div class="col-md-6 col-md-offset-3" style="margin-top: 50px;">
            <div class="panel panel-default" style="box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-radius: 12px; border: none;">
                
                <div class="panel-heading" style="background-color: #feefc3; border-radius: 12px 12px 0 0; border-bottom: none; padding: 20px; text-align: center;">
                    <h3 style="margin: 0; font-weight: bold; color: #202124;">
                        <i class="fas fa-user-plus"></i> Đăng ký tài khoản
                    </h3>
                    <p style="margin-top: 5px; margin-bottom: 0; color: #5f6368; font-size: 13px;">Bắt đầu tạo và quản lý ghi chú của bạn</p>
                </div>

                <div class="panel-body" style="padding: 30px;">
                    <form class="form-horizontal" method="POST" action="{{ route('register') }}">
                        {{ csrf_field() }}

                        <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
                            <label for="name" class="col-md-4 control-label" style="color: #5f6368; font-weight: 600;">Họ và tên</label>

                            <div class="col-md-7">
                                <div class="input-group">
                                    <span class="input-group-addon" style="background: #f1f3f4; border: none; border-radius: 8px 0 0 8px;"><i class="fas fa-user" style="color: #999;"></i></span>
                                    <input id="name" type="text" class="form-control" name="name" value="{{ old('name') }}" required autofocus placeholder="Ví dụ: Đào Nguyên Bảo" style="border: none; background: #f1f3f4; border-radius: 0 8px 8px 0; box-shadow: none; height: 40px;">
                                </div>

                                @if ($errors->has('name'))
                                    <span class="help-block" style="color: #e74c3c;">
                                        <strong>{{ $errors->first('name') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}" style="margin-top: 20px;">
                            <label for="email" class="col-md-4 control-label" style="color: #5f6368; font-weight: 600;">Địa chỉ Email</label>

                            <div class="col-md-7">
                                <div class="input-group">
                                    <span class="input-group-addon" style="background: #f1f3f4; border: none; border-radius: 8px 0 0 8px;"><i class="fas fa-envelope" style="color: #999;"></i></span>
                                    <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required placeholder="email@example.com" style="border: none; background: #f1f3f4; border-radius: 0 8px 8px 0; box-shadow: none; height: 40px;">
                                </div>

                                @if ($errors->has('email'))
                                    <span class="help-block" style="color: #e74c3c;">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}" style="margin-top: 20px;">
                            <label for="password" class="col-md-4 control-label" style="color: #5f6368; font-weight: 600;">Mật khẩu</label>

                            <div class="col-md-7">
                                <div class="input-group">
                                    <span class="input-group-addon" style="background: #f1f3f4; border: none; border-radius: 8px 0 0 8px;"><i class="fas fa-lock" style="color: #999;"></i></span>
                                    <input id="password" type="password" class="form-control" name="password" required placeholder="Ít nhất 6 ký tự" style="border: none; background: #f1f3f4; border-radius: 0 8px 8px 0; box-shadow: none; height: 40px;">
                                </div>

                                @if ($errors->has('password'))
                                    <span class="help-block" style="color: #e74c3c;">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 20px;">
                            <label for="password-confirm" class="col-md-4 control-label" style="color: #5f6368; font-weight: 600;">Xác nhận lại</label>

                            <div class="col-md-7">
                                <div class="input-group">
                                    <span class="input-group-addon" style="background: #f1f3f4; border: none; border-radius: 8px 0 0 8px;"><i class="fas fa-check-circle" style="color: #999;"></i></span>
                                    <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required placeholder="Nhập lại mật khẩu" style="border: none; background: #f1f3f4; border-radius: 0 8px 8px 0; box-shadow: none; height: 40px;">
                                </div>
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 35px; margin-bottom: 0;">
                            <div class="col-md-7 col-md-offset-4">
                                <button type="submit" class="btn btn-warning" style="background-color: #202124; border-color: #202124; color: white; border-radius: 20px; font-weight: bold; padding: 10px 30px; width: 100%; font-size: 15px;">
                                    Đăng ký ngay
                                </button>
                                <div style="text-align: center; margin-top: 15px;">
                                    <span style="color: #5f6368; font-size: 13px;">Đã có tài khoản?</span> 
                                    <a href="{{ route('login') }}" style="color: #1a73e8; font-weight: bold; text-decoration: none; font-size: 13px;">Đăng nhập</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection