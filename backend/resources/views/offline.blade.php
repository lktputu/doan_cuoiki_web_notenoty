@extends('layouts.app')

@section('content')
<div class="container text-center" style="margin-top: 50px;">
    <div style="font-size: 80px; color: #5f6368; margin-bottom: 20px;">
        <i class="fas fa-wifi" style="position: relative;">
            <i class="fas fa-slash" style="position: absolute; left: 0; color: #d93025; font-size: 90px;"></i>
        </i>
    </div>
    <h2 style="font-weight: bold; color: #202124;">Bạn đang ngoại tuyến (Offline)</h2>
    <p style="color: #5f6368; font-size: 16px;">Vui lòng kiểm tra lại kết nối mạng. Ứng dụng đang được lưu tạm trong bộ nhớ đệm (Cache) để bảo vệ dữ liệu của bạn.</p>
    
    <button onclick="window.location.reload()" class="btn btn-primary" style="margin-top: 20px; background-color: #fbbc04; border: none; color: #202124; font-weight: bold; padding: 10px 30px; border-radius: 20px;">
        <i class="fas fa-sync"></i> Thử lại
    </button>
</div>
@endsection