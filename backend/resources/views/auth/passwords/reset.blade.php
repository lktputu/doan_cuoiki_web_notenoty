@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<div class="container">
    <div class="row">
        <div class="col-md-6 col-md-offset-3" style="margin-top: 50px;">
            <div class="panel panel-default" style="box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-radius: 12px; border: none;">
                
                <div class="panel-heading" style="background-color: #feefc3; border-radius: 12px 12px 0 0; border-bottom: none; padding: 20px; text-align: center;">
                    <h3 style="margin: 0; font-weight: bold; color: #202124;">
                        <i class="fas fa-unlock-alt"></i> Đặt lại mật khẩu
                    </h3>
                    <p style="margin-top: 5px; margin-bottom: 0; color: #5f6368; font-size: 13px;">Tạo mật khẩu mới cho tài khoản của bạn</p>
                </div>

                <div class="panel-body" style="padding: 30px;">
                    <form class="form-horizontal" method="POST" action="{{ route('password.request') }}">
                        {{ csrf_field() }}

                        <input type="hidden" name="token" value="{{ $token }}">

                        <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                            <label for="email" class="col-md-4 control-label" style="color: #5f6368; font-weight: 600;">Tài khoản Email</label>

                            <div class="col-md-7">
                                <div class="input-group">
                                    <span class="input-group-addon" style="background: #e8eaed; border: none; border-radius: 8px 0 0 8px;"><i class="fas fa-envelope" style="color: #999;"></i></span>
                                    <input id="email" type="email" class="form-control" name="email" value="{{ $email or old('email') }}" required readonly style="border: none; background: #e8eaed; border-radius: 0 8px 8px 0; box-shadow: none; height: 40px; color: #5f6368; font-weight: bold; cursor: not-allowed;">
                                </div>

                                @if ($errors->has('email'))
                                    <span class="help-block" style="color: #e74c3c;">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}" style="margin-top: 20px;">
                            <label for="password" class="col-md-4 control-label" style="color: #5f6368; font-weight: 600;">Mật khẩu mới</label>

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

                        <div class="form-group{{ $errors->has('password_confirmation') ? ' has-error' : '' }}" style="margin-top: 20px;">
                            <label for="password-confirm" class="col-md-4 control-label" style="color: #5f6368; font-weight: 600;">Xác nhận lại</label>
                            <div class="col-md-7">
                                <div class="input-group">
                                    <span class="input-group-addon" style="background: #f1f3f4; border: none; border-radius: 8px 0 0 8px;"><i class="fas fa-check-circle" style="color: #999;"></i></span>
                                    <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required placeholder="Nhập lại mật khẩu mới" style="border: none; background: #f1f3f4; border-radius: 0 8px 8px 0; box-shadow: none; height: 40px;">
                                </div>

                                @if ($errors->has('password_confirmation'))
                                    <span class="help-block" style="color: #e74c3c;">
                                        <strong>{{ $errors->first('password_confirmation') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 30px; margin-bottom: 0;">
                            <div class="col-md-7 col-md-offset-4">
                                <button type="submit" class="btn btn-warning" style="background-color: #202124; border-color: #202124; color: white; border-radius: 20px; font-weight: bold; padding: 10px 30px; width: 100%; font-size: 15px;">
                                    Đổi mật khẩu
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection