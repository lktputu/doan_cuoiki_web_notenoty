@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2" style="margin-top: 30px;">
            
            @if(session('success'))
                <div class="alert alert-success" style="border-radius: 8px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.1); background-color: #e6f4ea; border-color: #ceead6; color: #137333;">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger" style="border-radius: 8px;">
                    <ul style="margin: 0; padding-left: 15px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="panel panel-default" style="box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-radius: 16px; border: none; overflow: hidden;">
                
                <div style="height: 120px; background: linear-gradient(135deg, #feefc3 0%, #fce8b2 100%); position: relative; display: flex; justify-content: flex-end; padding: 20px; gap: 10px;">
                    
                    <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#changePasswordModal" style="background: #ea4335; color: #fff; border: none; border-radius: 20px; font-weight: bold; padding: 6px 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); height: max-content; margin-right: 10px;">
                        <i class="fas fa-key"></i> Đổi mật khẩu
                    </button>

                    <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editProfileModal" style="background: #202124; color: #fff; border: none; border-radius: 20px; font-weight: bold; padding: 6px 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); height: max-content;">
                        <i class="fas fa-pen"></i> Chỉnh sửa hồ sơ
                    </button>
                </div>

                <div class="panel-body" style="padding: 0 30px 30px 30px; text-align: center; position: relative;">
                    
                    <div style="margin-top: -60px; margin-bottom: 15px;">
                        @if($user->avatar)
                            <img src="{{ asset('uploads/avatars/' . $user->avatar) }}" style="width: 120px; height: 120px; object-fit: cover; border: 5px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border-radius: 50%; background: #fff;">
                        @else
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=202124&color=fff&size=120&rounded=true&bold=true" style="border: 5px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border-radius: 50%;">
                        @endif
                    </div>

                    <h2 style="font-weight: bold; color: #202124; margin-top: 0; margin-bottom: 5px;">{{ $user->name }}</h2>
                    <p style="color: #5f6368; font-size: 15px;"><i class="fas fa-envelope"></i> {{ $user->email }}</p>

                    <div style="margin-top: 15px;">
                        @if($user->is_active == 1)
                            <span style="background-color: #e6f4ea; color: #137333; padding: 6px 15px; border-radius: 20px; font-size: 13px; font-weight: bold;"><i class="fas fa-check-circle"></i> Đã xác thực Email</span>
                        @else
                            <span style="background-color: #fdf6e3; color: #d93025; padding: 6px 15px; border-radius: 20px; font-size: 13px; font-weight: bold;"><i class="fas fa-exclamation-circle"></i> Chưa xác thực</span>
                        @endif
                        <span style="background-color: #f1f3f4; color: #5f6368; padding: 6px 15px; border-radius: 20px; font-size: 13px; font-weight: bold; margin-left: 10px;"><i class="fas fa-calendar-alt"></i> Tham gia: {{ $user->created_at->format('d/m/Y') }}</span>
                    </div>

                    <hr style="border-color: #eee; margin: 30px 0;">

                    <div class="row">
                        <div class="col-xs-6" style="border-right: 1px solid #eee;">
                            <h3 style="font-weight: bold; color: #202124; margin: 0; font-size: 28px;">{{ $noteCount }}</h3>
                            <p style="color: #5f6368; font-size: 13px; margin: 5px 0 0 0; font-weight: 600; text-transform: uppercase;">Ghi chú của tôi</p>
                        </div>
                        <div class="col-xs-6">
                            <h3 style="font-weight: bold; color: #1a73e8; margin: 0; font-size: 28px;">{{ $sharedCount }}</h3>
                            <p style="color: #5f6368; font-size: 13px; margin: 5px 0 0 0; font-weight: 600; text-transform: uppercase;">Được chia sẻ</p>
                        </div>
                    </div>

                </div>
            </div>

            <div class="panel panel-default" style="box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-radius: 16px; border: none; margin-top: 20px;">
                <div class="panel-heading" style="background-color: #f8f9fa; border-radius: 16px 16px 0 0; border-bottom: 1px solid #eee; padding: 15px 30px;">
                    <h4 style="margin: 0; font-weight: bold; color: #202124;"><i class="fas fa-cog"></i> Cài đặt cá nhân</h4>
                </div>
                <div class="panel-body" style="padding: 25px 30px;">
                    
                    <form action="{{ route('profile.preferences') }}" method="POST">
                        {{ csrf_field() }}
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <strong style="color: #202124; font-size: 16px;">Chế độ ban đêm (Dark Mode)</strong>
                                <p style="color: #5f6368; font-size: 13px; margin: 5px 0 0 0;">Chuyển giao diện sang màu tối để bảo vệ mắt</p>
                            </div>
                            <div>
                                <label class="pref-switch">
                                  <input type="checkbox" name="dark_mode" value="1" {{ $user->dark_mode == 1 ? 'checked' : '' }} onchange="this.form.submit()">
                                  <span class="pref-slider pref-round"></span>
                                </label>
                            </div>
                        </div>
                    </form>

                </div>
            </div>

            <style>
                .pref-switch { position: relative; display: inline-block; width: 54px; height: 30px; margin: 0; }
                .pref-switch input { opacity: 0; width: 0; height: 0; }
                .pref-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; }
                .pref-slider:before { position: absolute; content: ""; height: 22px; width: 22px; left: 4px; bottom: 4px; background-color: white; transition: .4s; }
                input:checked + .pref-slider { background-color: #202124; }
                input:checked + .pref-slider:before { transform: translateX(24px); }
                .pref-round { border-radius: 34px; }
                .pref-round:before { border-radius: 50%; }
            </style>

            <div class="text-center" style="margin-top: 20px;">
                <a href="{{ url('/home') }}" class="btn btn-default" style="border-radius: 20px; font-weight: bold; padding: 8px 25px; color: #5f6368; border: none; background-color: #f1f3f4; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"><i class="fas fa-arrow-left"></i> Quay lại Bảng ghi chú</a>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="editProfileModal" tabindex="-1" role="dialog" aria-labelledby="editProfileModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
      
      <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
          {{ csrf_field() }}
          <div class="modal-header" style="background-color: #feefc3; border-radius: 12px 12px 0 0; border-bottom: none;">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="editProfileModalLabel" style="font-weight: bold; color: #202124;"><i class="fas fa-pen"></i> Chỉnh sửa Hồ sơ</h4>
          </div>
          
          <div class="modal-body" style="padding: 20px;">
              <div class="form-group">
                  <label style="color: #5f6368;">Họ và tên hiển thị</label>
                  <input type="text" class="form-control" name="name" value="{{ $user->name }}" required style="border-radius: 8px; background: #f1f3f4; border: none; box-shadow: none; height: 40px;">
              </div>
              
              <div class="form-group" style="margin-top: 15px;">
                  <label style="color: #5f6368;">Ảnh đại diện (Avatar)</label>
                  <input type="file" class="form-control" name="avatar" accept="image/*" style="border-radius: 8px; background: #f1f3f4; border: none; box-shadow: none; padding-top: 8px;">
                  <small style="color: #999;">Hỗ trợ .jpg, .png. Tối đa 2MB. Bỏ trống nếu không muốn đổi ảnh.</small>
              </div>
          </div>
          
          <div class="modal-footer" style="border-top: none; padding: 15px 20px;">
            <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius: 20px; font-weight: bold; background: #f1f3f4; border: none;">Hủy bỏ</button>
            <button type="submit" class="btn btn-primary" style="border-radius: 20px; font-weight: bold; background: #202124; border: none; padding: 6px 20px;">Lưu thay đổi</button>
          </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog" aria-labelledby="changePasswordModalLabel">
  <div class="modal-dialog" role="document" style="max-width: 400px;">
    <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
      
      <form action="{{ route('profile.change-password') }}" method="POST">
          {{ csrf_field() }}
          <div class="modal-header" style="background-color: #fad2cf; border-radius: 12px 12px 0 0; border-bottom: none;">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="changePasswordModalLabel" style="font-weight: bold; color: #b31412;"><i class="fas fa-key"></i> Đổi mật khẩu</h4>
          </div>
          
          <div class="modal-body" style="padding: 20px;">
              <div class="form-group">
                  <label style="color: #5f6368;">Mật khẩu hiện tại</label>
                  <input type="password" class="form-control" name="current_password" required style="border-radius: 8px; background: #f1f3f4; border: none; box-shadow: none; height: 40px;">
              </div>
              
              <div class="form-group" style="margin-top: 15px;">
                  <label style="color: #5f6368;">Mật khẩu mới</label>
                  <input type="password" class="form-control" name="new_password" required placeholder="Ít nhất 6 ký tự" style="border-radius: 8px; background: #f1f3f4; border: none; box-shadow: none; height: 40px;">
              </div>

              <div class="form-group" style="margin-top: 15px;">
                  <label style="color: #5f6368;">Xác nhận mật khẩu mới</label>
                  <input type="password" class="form-control" name="new_password_confirmation" required placeholder="Nhập lại mật khẩu mới" style="border-radius: 8px; background: #f1f3f4; border: none; box-shadow: none; height: 40px;">
              </div>
          </div>
          
          <div class="modal-footer" style="border-top: none; padding: 15px 20px;">
            <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius: 20px; font-weight: bold; background: #f1f3f4; border: none;">Hủy bỏ</button>
            <button type="submit" class="btn btn-danger" style="border-radius: 20px; font-weight: bold; background: #ea4335; border: none; padding: 6px 20px;">Xác nhận đổi</button>
          </div>
      </form>
    </div>
  </div>
</div>
@endsection