@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<div class="container">
    <div class="row">
        <div class="col-md-6 col-md-offset-3" style="margin-top: 50px;">
            
            @if (session('status'))
                <div class="alert alert-success" style="border-radius: 8px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.1); background-color: #e6f4ea; border-color: #ceead6; color: #137333;">
                    <i class="fas fa-check-circle"></i> {{ session('status') }}
                </div>
            @endif

            <div class="panel panel-default" style="box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-radius: 12px; border: none;">
                
                <div class="panel-heading" style="background-color: #feefc3; border-radius: 12px 12px 0 0; border-bottom: none; padding: 20px; text-align: center;">
                    <h3 style="margin: 0; font-weight: bold; color: #202124;">
                        <i class="fas fa-key"></i> Khôi phục mật khẩu
                    </h3>
                    <p style="margin-top: 5px; margin-bottom: 0; color: #5f6368; font-size: 13px;">Nhập email để nhận liên kết tạo mật khẩu mới</p>
                </div>

                <div class="panel-body" style="padding: 30px;">
                    <form class="form-horizontal" method="POST" action="{{ route('password.email') }}">
                        {{ csrf_field() }}

                        <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                            <label for="email" class="col-md-4 control-label" style="color: #5f6368; font-weight: 600;">Địa chỉ Email</label>

                            <div class="col-md-7">
                                <div class="input-group">
                                    <span class="input-group-addon" style="background: #f1f3f4; border: none; border-radius: 8px 0 0 8px;"><i class="fas fa-envelope" style="color: #999;"></i></span>
                                    <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus placeholder="email@example.com" style="border: none; background: #f1f3f4; border-radius: 0 8px 8px 0; box-shadow: none; height: 40px;">
                                </div>

                                @if ($errors->has('email'))
                                    <span class="help-block" style="color: #e74c3c;">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 25px; margin-bottom: 0;">
                            <div class="col-md-7 col-md-offset-4">
                                <button type="submit" class="btn btn-warning" style="background-color: #202124; border-color: #202124; color: white; border-radius: 20px; font-weight: bold; padding: 10px 30px; width: 100%; font-size: 15px;">
                                    Gửi liên kết khôi phục
                                </button>
                                
                                <div style="text-align: center; margin-top: 15px;">
                                    <a class="btn btn-link" href="{{ route('login') }}" style="color: #1a73e8; font-size: 13px; text-decoration: none; font-weight: bold;">
                                        Quay lại Đăng nhập
                                    </a>
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