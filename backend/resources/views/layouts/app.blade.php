<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel Note') }}</title>

    <!-- TIÊU CHÍ 27: LIÊN KẾT FILE MANIFEST VÀ THEME COLOR -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#fbbc04">

    <link rel="icon" href="https://cdn-icons-png.flaticon.com/512/752/752326.png" type="image/png">

    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        /* Cuộn trang mượt mà */
        html { scroll-behavior: smooth; }
        
        /* ACCESSIBILITY: Viền báo hiệu khi dùng phím Tab di chuyển */
        a:focus, button:focus, input:focus, textarea:focus { 
            outline: 2px solid #1a73e8 !important; 
            outline-offset: 2px; 
        }

        /* UX: Hiệu ứng hover nổi lên cho các nút bấm */
        .btn { transition: all 0.2s ease-in-out; }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important; }
        .btn:active { transform: translateY(0); box-shadow: none !important; }

        /* UX: Làm mượt hiệu ứng dropdown menu */
        .dropdown-menu { animation: fadeIn 0.2s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>

    @if(Auth::check() && Auth::user()->dark_mode == 1)
    <style>
        body { background-color: #202124 !important; }
        .navbar-default { background-color: #2d2e30 !important; border-bottom: 1px solid #3c4043 !important; }
        .navbar-default .navbar-brand { color: #e8eaed !important; }
        .navbar-default .navbar-nav>li>a { color: #e8eaed !important; }
        
        .panel { background-color: #2d2e30 !important; border: 1px solid #3c4043 !important; box-shadow: none !important; }
        .panel-heading, .modal-header { background-color: #3c4043 !important; border-bottom: 1px solid #5f6368 !important; }
        .dropdown-menu, .modal-content { background-color: #2d2e30 !important; border: 1px solid #3c4043 !important; }
        .dropdown-menu>li>a { color: #e8eaed !important; }
        .dropdown-menu>li>a:hover { background-color: #3c4043 !important; }
        
        h1, h2, h3, h4, h5, p, label, strong { color: #e8eaed !important; }
        hr { border-color: #3c4043 !important; }
        
        .form-control, .input-group-addon { background-color: #3c4043 !important; color: #e8eaed !important; border: 1px solid #5f6368 !important; }
        .modal-footer { border-top: 1px solid #5f6368 !important; }

        span[style*="#e6f4ea"] { background-color: #137333 !important; color: #e8eaed !important; } 
        span[style*="#fdf6e3"] { background-color: #8c4a00 !important; color: #e8eaed !important; } 
        span[style*="#f1f3f4"] { background-color: #3c4043 !important; color: #e8eaed !important; } 

        .label { background-color: #3c4043 !important; color: #8ab4f8 !important; border: 1px solid #5f6368 !important; padding: 4px 8px; }
        
        .btn-default { background-color: #3c4043 !important; color: #e8eaed !important; border-color: #5f6368 !important; }
        .btn-default:hover { background-color: #5f6368 !important; color: #fff !important; }
    </style>
    @endif

    <style>
        body, .panel, .btn, input, .modal-content, img { transition: all 0.3s ease-in-out; }

        .dropdown-toggle * { pointer-events: none; }

        /* TỐI ƯU CHO ĐIỆN THOẠI */
        @media (max-width: 767px) {
            .container { padding-left: 10px; padding-right: 10px; }
            .panel-body { padding: 20px 15px !important; }
            h2 { font-size: 22px !important; }
            h3 { font-size: 24px !important; } 
            
            .panel > div[style*="background: linear-gradient"] { 
                height: auto !important; 
                flex-direction: column !important; 
                padding: 15px 15px 65px 15px !important; 
            }
            .panel > div[style*="background: linear-gradient"] button { 
                width: 100% !important; margin: 0 0 10px 0 !important; padding: 10px !important; 
            }
            
            img[style*="width: 120px"] { 
                width: 90px !important; height: 90px !important; margin-top: -45px !important; 
            }

            .navbar-nav { margin: 0; }
            .navbar-nav .dropdown-menu { 
                background-color: rgba(0,0,0,0.03) !important; 
                border: none !important; box-shadow: none !important; 
                padding: 10px 15px !important; border-radius: 8px !important; margin-bottom: 15px !important;
            }
            .navbar-right .dropdown-toggle { display: flex; align-items: center; padding: 15px !important; }
            
            .navbar-nav .open .dropdown-menu {
                position: static !important; float: none !important; width: 100% !important;
                display: block !important; visibility: visible !important; opacity: 1 !important; 
                animation: none !important; 
            }

            .modal-dialog { margin: 10px; }
            .modal-footer button { width: 100%; margin-bottom: 5px; margin-left: 0 !important; }
            
            .pref-switch { flex-shrink: 0; }
        }
    </style>
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-default navbar-static-top" role="navigation" aria-label="Main Navigation">
            <div class="container">
                <div class="navbar-header">

                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#app-navbar-collapse" aria-expanded="false" aria-label="Mở menu điều hướng">
                        <span class="sr-only">Toggle Navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>

                    <a class="navbar-brand" href="{{ url('/home') }}" title="Trang chủ Note App">
                        <i class="fas fa-sticky-note" style="color: #fbbc04; margin-right: 5px;"></i> {{ config('app.name', 'Laravel') }}
                    </a>
                </div>

                <div class="collapse navbar-collapse" id="app-navbar-collapse">
                    <ul class="nav navbar-nav">
                        &nbsp;
                    </ul>

                    <ul class="nav navbar-nav navbar-right">
                        @if (Auth::guest())
                            <li><a href="{{ route('login') }}" style="font-weight: bold; color: #5f6368;">Đăng nhập</a></li>
                            <li><a href="{{ route('register') }}" style="font-weight: bold; color: #5f6368;">Đăng ký</a></li>
                        @else
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false" aria-haspopup="true" style="font-weight: bold; color: #202124; display: flex; align-items: center;">
                                    
                                    @if(Auth::user()->avatar)
                                        <img src="{{ asset('uploads/avatars/' . Auth::user()->avatar) }}" alt="Avatar của {{ Auth::user()->name }}" style="width: 32px; height: 32px; object-fit: cover; margin-right: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.2); border-radius: 50%;">
                                    @else
                                        <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=feefc3&color=202124&rounded=true&bold=true" alt="Avatar mặc định" style="width: 32px; height: 32px; margin-right: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.2); border-radius: 50%;">
                                    @endif
                                    
                                    {{ Auth::user()->name }} <span class="caret" style="margin-left: 5px;"></span>
                                </a>

                                <ul class="dropdown-menu" role="menu" style="border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border: none; padding: 5px 0;">
                                    
                                    <li>
                                        <a href="{{ route('profile') }}" style="padding: 10px 20px; color: #202124; font-weight: bold; display: flex; align-items: center;" aria-label="Xem hồ sơ cá nhân">
                                            <i class="fas fa-user-circle" style="margin-right: 10px; color: #f39c12;"></i> Hồ sơ của tôi
                                        </a>
                                    </li>
                                    <li role="separator" class="divider" style="margin: 5px 0;"></li>

                                    <li>
                                        <a href="{{ route('logout') }}"
                                            onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();" style="padding: 10px 20px; color: #d93025; font-weight: bold; display: flex; align-items: center;" aria-label="Đăng xuất khỏi hệ thống">
                                            <i class="fas fa-sign-out-alt" style="margin-right: 10px;"></i> Đăng xuất
                                        </a>

                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                            {{ csrf_field() }}
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </nav>

        <main id="main-content" role="main">
            @yield('content')
        </main>
    </div>

    <script src="{{ asset('js/app.js') }}"></script>
    
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var mobileDropdowns = document.querySelectorAll('.navbar-right .dropdown-toggle');
            mobileDropdowns.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    if (window.innerWidth < 768) {
                        e.preventDefault(); 
                        var parentLi = this.parentElement;
                        parentLi.classList.toggle('open');
                    }
                });
            });

            var forms = document.querySelectorAll('form');
            forms.forEach(function(form) {
                form.addEventListener('submit', function() {
                    var submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        var originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
                        submitBtn.style.opacity = '0.8';
                        submitBtn.style.pointerEvents = 'none'; 
                    }
                });
            });
        });
    </script>

    <!-- TIÊU CHÍ 27: SERVICE WORKER & ĐỒNG BỘ OFFLINE -->
    <script>
        // Đăng ký Service Worker để Caching dữ liệu
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js').then(function(registration) {
                    console.log('ServiceWorker đã đăng ký thành công!');
                }, function(err) {
                    console.log('Đăng ký ServiceWorker thất bại: ', err);
                });
            });
        }

        // TÍNH NĂNG OFFLINE DATA SYNCHRONIZATION (Thông báo & Đồng bộ trạng thái mạng)
        window.addEventListener('offline', function() {
            // Khi rớt mạng, lưu tạm thông báo hoặc vô hiệu hóa nút submit để chống mất dữ liệu
            var submitBtns = document.querySelectorAll('button[type="submit"]');
            submitBtns.forEach(btn => {
                btn.dataset.originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-plane-slash"></i> Mất mạng - Không thể lưu';
                btn.classList.add('disabled');
                btn.style.pointerEvents = 'none';
            });
            alert('⚠️ CẢNH BÁO: Bạn đã mất kết nối mạng! Không nên thao tác lúc này để tránh mất dữ liệu.');
        });

        window.addEventListener('online', function() {
            // Khi có mạng lại, mở khóa các nút bấm và thông báo đồng bộ
            var submitBtns = document.querySelectorAll('button[type="submit"]');
            submitBtns.forEach(btn => {
                if (btn.dataset.originalText) {
                    btn.innerHTML = btn.dataset.originalText;
                }
                btn.classList.remove('disabled');
                btn.style.pointerEvents = 'auto';
            });
            alert('✅ TUYỆT VỜI: Mạng đã khôi phục! Hệ thống đã đồng bộ dữ liệu. Bạn có thể lưu bình thường.');
        });
    </script>
</body>
</html>