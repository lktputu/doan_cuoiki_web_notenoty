@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<div class="container">
    <div class="row">
        <div class="col-md-3">
            <div class="list-group" style="box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding-right: 10px;">
                <a href="{{ url('/home') }}" class="list-group-item" style="border: none; border-radius: 0 25px 25px 0; margin-bottom: 5px; color: {{ (!$isSharedPage && !request()->has('label_id')) ? '#202124' : '#5f6368' }}; background-color: {{ (!$isSharedPage && !request()->has('label_id')) ? '#feefc3' : 'transparent' }}; font-weight: {{ (!$isSharedPage && !request()->has('label_id')) ? 'bold' : 'normal' }};">
                    <i class="fas fa-lightbulb" style="width: 25px;"></i> Ghi chú
                </a>
                <a href="{{ url('/home?shared=1') }}" class="list-group-item" style="border: none; border-radius: 0 25px 25px 0; margin-bottom: 5px; color: {{ $isSharedPage ? '#202124' : '#5f6368' }}; background: {{ $isSharedPage ? '#feefc3' : 'transparent' }}; font-weight: {{ $isSharedPage ? 'bold' : 'normal' }};">
                    <i class="fas fa-user-friends" style="width: 25px;"></i> Được chia sẻ
                </a>
                <div class="list-group-item" style="background: transparent; font-weight: bold; font-size: 11px; letter-spacing: 0.8px; color: #5f6368; border: none; padding-top: 15px; padding-bottom: 5px;">NHÃN CỦA TÔI</div>
                
                @if(isset($labels))
                    @foreach($labels as $label)
                        <a href="{{ url('/home?label_id=' . $label->id) }}" class="list-group-item" style="border: none; border-radius: 0 25px 25px 0; margin-bottom: 2px; color: {{ request('label_id') == $label->id ? '#202124' : '#5f6368' }}; background: {{ request('label_id') == $label->id ? '#feefc3' : 'transparent' }}; font-weight: {{ request('label_id') == $label->id ? 'bold' : 'normal' }};">
                            <i class="fas fa-tag" style="width: 25px;"></i> {{ $label->name }}
                        </a>
                    @endforeach
                @endif
                <a href="#" class="list-group-item" style="border: none; border-radius: 0 25px 25px 0; margin-top: 5px; color: #5f6368; background: transparent; font-style: italic;" data-toggle="modal" data-target="#manageLabelsModal">
                    <i class="fas fa-pencil-alt" style="width: 25px;"></i> Chỉnh sửa nhãn...
                </a>
            </div>
        </div>

        <div class="col-md-9">
            <div class="row" style="margin-bottom: 25px;">
                <div class="col-md-12">
                    <form action="{{ url('/home') }}" method="GET">
                        <div class="input-group" style="box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden;">
                            <input type="text" name="search" class="form-control input-lg" placeholder="🔍 Tìm kiếm..." value="{{ request('search') }}" style="border: none; box-shadow: none;">
                            <span class="input-group-btn">
                                @if(request()->has('search') && request('search') != '')
                                    <a href="{{ url('/home') }}" class="btn btn-default input-lg" style="border: none; background: #fff; color: #e74c3c;"><i class="fas fa-times"></i></a>
                                @endif
                                <button class="btn btn-default input-lg" type="submit" style="border: none; background: #fff; color: #5f6368;"><i class="fas fa-search"></i></button>
                            </span>
                        </div>
                    </form>
                </div>
            </div>
            
            @if(!$isSharedPage)
            <div class="panel panel-default" style="box-shadow: 0 3px 6px rgba(0,0,0,0.1); border-radius: 8px; border: none;">
                <div class="panel-body" style="padding: 20px;">
                    <form action="{{ route('notes.store') }}" method="POST" enctype="multipart/form-data">
                        {{ csrf_field() }}
                        <div id="image-preview-container" style="display: none; position: relative; margin: -20px -20px 15px -20px;">
                            <img id="image-preview" src="" style="width: 100%; border-top-left-radius: 8px; border-top-right-radius: 8px; display: block;">
                            <button type="button" id="remove-preview" class="btn btn-default btn-xs" style="position: absolute; top: 10px; right: 10px; border-radius: 50%; width: 28px; height: 28px; opacity: 0.8; background: #fff; border: none;"><i class="fas fa-times"></i></button>
                        </div>
                        <div class="form-group"><input type="text" name="title" class="form-control input-lg" placeholder="Tiêu đề..." style="border: none; box-shadow: none; font-weight: bold; font-size: 18px; padding: 0;"></div>
                        <div class="form-group"><textarea name="content" class="form-control" rows="2" placeholder="Tạo ghi chú mới..." required style="border: none; box-shadow: none; resize: none; padding: 0; font-size: 15px;"></textarea></div>
                        <hr style="margin: 10px 0 15px 0; border-color: #eee;">
                        <div class="clearfix">
                            <div class="pull-left">
                                <label class="btn btn-default btn-sm" style="border-radius: 20px; border: none; background: #f1f3f4; cursor: pointer; margin-right: 5px;"><i class="far fa-image"></i> <input type="file" name="image" id="note-image-input" accept="image/*" style="display: none;"></label>
                                <div class="dropdown" style="display: inline-block; margin-right: 5px;">
                                    <button class="btn btn-default btn-sm dropdown-toggle" type="button" data-toggle="dropdown" style="border-radius: 20px; border: none; background: #f1f3f4;"><i class="fas fa-tags"></i></button>
                                    <ul class="dropdown-menu" style="padding: 10px; min-width: 200px;">
                                        @if(isset($labels) && $labels->count() > 0)
                                            @foreach($labels as $label)
                                            <li><label style="font-weight: normal; display: block; cursor: pointer; padding: 5px 10px; margin: 0;"><input type="checkbox" name="labels[]" value="{{ $label->id }}"> {{ $label->name }}</label></li>
                                            @endforeach
                                        @else
                                            <li style="padding: 5px 10px; color: #999; font-size: 12px;">Chưa có nhãn</li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-warning pull-right" style="font-weight: bold; border-radius: 20px; padding: 6px 20px; background-color: #202124; border-color: #202124; color: white;">Lưu ghi chú</button>
                        </div>
                    </form>
                </div>
            </div>
            @else
                <h3 style="font-weight: bold; color: #202124; margin-top: 0;">Ghi chú được chia sẻ với bạn</h3>
            @endif

            <div class="text-right" style="margin-bottom: 15px;">
                <div class="btn-group" style="box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <button type="button" id="btn-grid" class="btn btn-default active" style="font-weight: bold; color: #333;" onclick="setGridView()"><i class="fas fa-th-large"></i> Lưới</button>
                    <button type="button" id="btn-list" class="btn btn-default" style="color: #777;" onclick="setListView()"><i class="fas fa-list"></i> Danh sách</button>
                </div>
            </div>

            <div class="row" id="notes-container">
                @foreach($notes as $note)
                @php
                    $isLocked = $note->password && !\Illuminate\Support\Facades\Session::get('unlocked_' . $note->id);
                @endphp
                <div class="note-item col-md-6 col-lg-4">
                    <div class="panel panel-default" style="box-shadow: 0 2px 5px rgba(0,0,0,0.08); border-radius: 8px; border: 1px solid #e0e0e0; transition: 0.3s; margin-bottom: 20px; {{ $isLocked ? 'background-color: #f8f9fa;' : '' }}">
                        <div class="panel-body" style="padding: 15px;">
                            @if(!$isSharedPage)
                            <button class="btn btn-link btn-xs pull-right btn-pin" data-id="{{ $note->id }}" style="color: {{ $note->is_pinned ? '#f1c40f' : '#999' }}; padding: 0; position: absolute; right: 25px; top: 15px; z-index: 10;"><i class="fas fa-thumbtack"></i></button>
                            @endif

                            @if($isLocked)
                                <div class="text-center" style="padding: 30px 10px;">
                                    <i class="fas fa-lock" style="font-size: 32px; color: #dadce0; margin-bottom: 15px;"></i>
                                    <h5 style="font-weight: bold; color: #5f6368;">Ghi chú được bảo mật</h5>
                                    <button class="btn btn-default btn-sm btn-unlock" data-id="{{ $note->id }}" style="border-radius: 20px; font-weight: bold; margin-top: 10px; border-color: #dadce0; color: #1a73e8;">Mở khóa để xem</button>
                                </div>
                            @else
                                @if($note->attachments->count() > 0)
                                    <div class="note-image" style="margin: -15px -15px 15px -15px;">
                                        @foreach($note->attachments as $attachment)
                                            <img src="{{ Storage::url($attachment->file_path) }}" style="width: 100%; border-top-left-radius: 8px; border-top-right-radius: 8px; display: block; margin-bottom: 5px;">
                                        @endforeach
                                    </div>
                                @endif
                                <h4 style="font-weight: bold; margin-top: 0; color: #333; font-size: 16px; padding-right: 20px;">{{ $note->title }}</h4>
                                <p style="color: #666; word-wrap: break-word; font-size: 14px; margin-bottom: 10px;">{!! nl2br(e($note->content)) !!}</p>
                                
                                @if($note->labels->count() > 0)
                                    <div style="margin-top: 10px;">
                                        @foreach($note->labels as $label)
                                            <span style="display: inline-block; background-color: #f1f3f4; color: #3c4043; border-radius: 12px; padding: 4px 10px; font-size: 11px; font-weight: bold; margin-right: 5px; margin-bottom: 5px;"><i class="fas fa-tag"></i> {{ $label->name }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                        </div>
                        
                        <div class="panel-footer" style="background: transparent; border-top: 1px solid #f5f5f5; text-align: right; padding: 8px 15px;">
                            @if(!$isSharedPage)
                                @if(!$isLocked)
                                    <button type="button" class="btn btn-default btn-xs btn-set-password" data-id="{{ $note->id }}" style="border: none; background: transparent; color: {{ $note->password ? '#e74c3c' : '#999' }}; float: left;"><i class="fas {{ $note->password ? 'fa-lock' : 'fa-unlock-alt' }}"></i></button>
                                    @if($note->password)
                                        <button type="button" class="btn btn-default btn-xs btn-lock-now" data-id="{{ $note->id }}" style="border: none; background: transparent; color: #2ecc71; float: left; margin-left: 10px;"><i class="fas fa-user-lock"></i> Khóa lại</button>
                                    @endif
                                    
                                    <button class="btn btn-default btn-xs btn-share" data-id="{{ $note->id }}" style="border: none; background: transparent; color: #1a73e8;" title="Chia sẻ ghi chú này"><i class="fas fa-share-alt"></i></button>
                                    <button type="button" class="btn btn-default btn-xs" style="border: none; background: transparent; color: #f39c12;" data-toggle="modal" data-target="#editModal{{ $note->id }}"><i class="fas fa-edit"></i></button>
                                    <form action="{{ route('notes.destroy', $note->id) }}" method="POST" style="display:inline;">
                                        {{ csrf_field() }} {{ method_field('DELETE') }}
                                        <button type="submit" class="btn btn-default btn-xs" style="border: none; background: transparent; color: #e74c3c;" onclick="return confirm('Em có chắc chắn muốn xóa?')"><i class="fas fa-trash"></i></button>
                                    </form>

                                    <div class="modal fade" id="editModal{{ $note->id }}" tabindex="-1" role="dialog">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content" style="border-radius: 8px; text-align: left;">
                                                <form action="{{ route('notes.update', $note->id) }}" method="POST">
                                                    {{ csrf_field() }} {{ method_field('PUT') }}
                                                    <div class="modal-header" style="border-bottom: 1px solid #eee;">
                                                        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                                                        <input type="text" name="title" class="form-control auto-save-input" value="{{ $note->title }}" style="border: none; box-shadow: none; font-weight: bold; font-size: 18px; padding: 0;">
                                                    </div>
                                                    <div class="modal-body">
                                                        <textarea name="content" class="form-control auto-save-input" rows="5" required style="border: none; box-shadow: none; resize: none; font-size: 15px; padding: 0;">{{ $note->content }}</textarea>
                                                    </div>
                                                    <div class="modal-footer" style="border-top: none; display: flex; justify-content: space-between; align-items: center;">
                                                        <div class="dropdown dropup" style="display: inline-block;">
                                                            <button class="btn btn-default btn-sm dropdown-toggle" type="button" data-toggle="dropdown" style="border-radius: 20px; border: none; background: #f1f3f4;"><i class="fas fa-tags"></i> Nhãn</button>
                                                            <ul class="dropdown-menu" style="padding: 10px; min-width: 200px; text-align: left;">
                                                                @if(isset($labels))
                                                                    @foreach($labels as $label)
                                                                    <li><label style="font-weight: normal; display: block; cursor: pointer; padding: 5px 10px; margin: 0;"><input type="checkbox" name="labels[]" class="auto-save-input" value="{{ $label->id }}" {{ $note->labels->contains($label->id) ? 'checked' : '' }}> {{ $label->name }}</label></li>
                                                                    @endforeach
                                                                @endif
                                                            </ul>
                                                        </div>
                                                        <div style="display: flex; align-items: center;">
                                                            <span class="auto-save-status" style="margin-right: 15px; font-size: 13px; font-weight: bold;"></span>
                                                            <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius: 20px; padding: 6px 20px;">Đóng</button>
                                                            <button type="submit" class="btn btn-warning" style="border-radius: 20px; font-weight: bold; padding: 6px 20px; background-color: #202124; border-color: #202124; color: white; margin-left: 5px;">Lưu thủ công</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @else
                                <div style="float: left; text-align: left; margin-top: 2px;">
                                    <div style="font-size: 11px; color: #2ecc71; font-weight: bold; margin-bottom: 4px;"><i class="fas fa-users"></i> Đồng tác giả</div>
                                    <div style="font-size: 11px; color: #1a73e8; background: #e8f0fe; padding: 3px 8px; border-radius: 12px; display: inline-block;" title="Ghi chú này được gửi từ tài khoản: {{ isset($note->user) ? $note->user->email : 'Không xác định' }}">
                                        <i class="fas fa-paper-plane"></i> Từ: {{ isset($note->user) ? $note->user->email : 'Không xác định' }}
                                    </div>
                                </div>
                                
                                @if(!$isLocked)
                                    <form action="{{ route('notes.removeShared', $note->id) }}" method="POST" style="display:inline; float: right; margin-top: 8px; margin-left: 10px;">
                                        {{ csrf_field() }}
                                        <button type="submit" class="btn btn-default btn-xs" title="Xóa khỏi danh sách của bạn (Không ảnh hưởng đến người gửi)" style="border: none; background: transparent; color: #e74c3c;" onclick="return confirm('Bạn có chắc muốn gỡ ghi chú này khỏi danh sách của mình không?\n(Điều này sẽ không làm xóa ghi chú gốc của người gửi)')"><i class="fas fa-trash"></i> Xóa</button>
                                    </form>

                                    <button type="button" class="btn btn-default btn-xs" title="Cùng chỉnh sửa (Real-time)" style="border: none; background: transparent; color: #f39c12; float: right; margin-top: 8px;" data-toggle="modal" data-target="#editModal{{ $note->id }}"><i class="fas fa-edit"></i> Sửa</button>
                                    
                                    <div class="modal fade" id="editModal{{ $note->id }}" tabindex="-1" role="dialog">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content" style="border-radius: 8px; text-align: left;">
                                                <form action="{{ route('notes.update', $note->id) }}" method="POST">
                                                    {{ csrf_field() }} {{ method_field('PUT') }}
                                                    <div class="modal-header" style="border-bottom: 1px solid #eee;">
                                                        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                                                        <input type="text" name="title" class="form-control auto-save-input" value="{{ $note->title }}" style="border: none; box-shadow: none; font-weight: bold; font-size: 18px; padding: 0;">
                                                    </div>
                                                    <div class="modal-body">
                                                        <textarea name="content" class="form-control auto-save-input" rows="5" required style="border: none; box-shadow: none; resize: none; font-size: 15px; padding: 0;">{{ $note->content }}</textarea>
                                                    </div>
                                                    <div class="modal-footer" style="border-top: none; display: flex; justify-content: flex-end; align-items: center;">
                                                        <span class="auto-save-status" style="margin-right: 15px; font-size: 13px; font-weight: bold;"></span>
                                                        <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius: 20px; padding: 6px 20px;">Đóng</button>
                                                        <button type="submit" class="btn btn-warning" style="border-radius: 20px; font-weight: bold; padding: 6px 20px; background-color: #202124; border-color: #202124; color: white; margin-left: 5px;">Lưu thủ công</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    @if($note->password)
                                        <button type="button" class="btn btn-default btn-xs btn-lock-now" data-id="{{ $note->id }}" style="border: none; background: transparent; color: #2ecc71; float: right; margin-top: 8px; margin-right: 5px;"><i class="fas fa-user-lock"></i> Khóa lại</button>
                                    @endif
                                @endif
                                <div style="clear: both;"></div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@if(isset($labels) && !$isSharedPage)
<div class="modal fade" id="manageLabelsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content" style="border-radius: 8px;">
            <div class="modal-header" style="border-bottom: 1px solid #eee;">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" style="font-weight: bold; font-size: 16px;">Chỉnh sửa nhãn</h4>
            </div>
            <div class="modal-body">
                <form action="{{ route('labels.store') }}" method="POST" style="margin-bottom: 20px;">
                    {{ csrf_field() }}
                    <div class="input-group">
                        <input type="text" name="name" class="form-control" placeholder="Tạo nhãn mới..." required style="border-radius: 4px 0 0 4px; box-shadow: none;">
                        <span class="input-group-btn">
                            <button class="btn btn-default" type="submit" style="border-radius: 0 4px 4px 0;"><i class="fas fa-check" style="color: #5f6368;"></i></button>
                        </span>
                    </div>
                </form>

                <div class="label-list">
                    @foreach($labels as $label)
                    <div style="margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;">
                        <form action="{{ route('labels.update', $label->id) }}" method="POST" style="flex-grow: 1; margin-right: 10px;">
                            {{ csrf_field() }} {{ method_field('PUT') }}
                            <div class="input-group" style="width: 100%;">
                                <span class="input-group-addon" style="background: transparent; border: none; padding-left: 0;"><i class="fas fa-tag" style="color: #999;"></i></span>
                                <input type="text" name="name" value="{{ $label->name }}" class="form-control input-sm" style="border: none; background: #f1f3f4; border-radius: 4px; box-shadow: none;" onchange="this.form.submit()" title="Sửa tên và Enter">
                            </div>
                        </form>
                        <form action="{{ route('labels.destroy', $label->id) }}" method="POST" style="margin: 0;">
                            {{ csrf_field() }} {{ method_field('DELETE') }}
                            <button type="submit" class="btn btn-link btn-xs text-danger" style="padding: 0; margin-top: 5px;"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer" style="border-top: none;">
                <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius: 20px; font-weight: bold; border: none; background: #f1f3f4;">Hoàn tất</button>
            </div>
        </div>
    </div>
</div>
@endif

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<script>
    function setGridView() { $('.note-item').removeClass('col-md-12').addClass('col-md-6 col-lg-4'); $('#btn-grid').addClass('active').css({'font-weight':'bold','color':'#333'}); $('#btn-list').removeClass('active').css({'font-weight':'normal','color':'#777'}); }
    function setListView() { $('.note-item').removeClass('col-md-6 col-lg-4').addClass('col-md-12'); $('#btn-list').addClass('active').css({'font-weight':'bold','color':'#333'}); $('#btn-grid').removeClass('active').css({'font-weight':'normal','color':'#777'}); }

    $(document).ready(function() {
        $('.dropdown-toggle').on('click', function(e) { e.preventDefault(); $('.dropdown-menu').not($(this).next('.dropdown-menu')).hide(); $(this).next('.dropdown-menu').toggle(); });
        $(document).on('click', function(e) { if (!$(e.target).closest('.dropdown').length && !$(e.target).closest('.dropup').length) { $('.dropdown-menu').hide(); } });
        $('.dropdown-menu input, .dropdown-menu label').click(function(e) { e.stopPropagation(); });

        var timeoutId;
        $('.auto-save-input').on('input change', function() {
            clearTimeout(timeoutId); var form = $(this).closest('form'); var url = form.attr('action'); var data = form.serialize(); var statusText = form.closest('.modal-content').find('.auto-save-status');
            statusText.text('Đang lưu... ⏳').css('color', '#f39c12');
            timeoutId = setTimeout(function() {
                $.ajax({ type: 'POST', url: url, data: data, success: function(res) { if(res.success) { statusText.text('Đã lưu tự động ✅').css('color', '#28a745'); setTimeout(function() { statusText.text(''); }, 2000); } } });
            }, 1000); 
        });

        $('#note-image-input').change(function() {
            var input = this; if (input.files && input.files[0]) { var reader = new FileReader(); reader.onload = function(e) { $('#image-preview').attr('src', e.target.result); $('#image-preview-container').slideDown('fast'); }; reader.readAsDataURL(input.files[0]); }
        });
        $('#remove-preview').click(function() { $('#note-image-input').val(''); $('#image-preview-container').slideUp('fast'); });

        $('.btn-pin').click(function() { var btn = $(this); var noteId = btn.data('id'); btn.css('opacity', '0.5'); $.ajax({ type: 'POST', url: '/notes/pin/' + noteId, data: { _token: '{{ csrf_token() }}' }, success: function(res) { if(res.success) window.location.reload(); } }); });

        $('.btn-set-password').click(function() {
            var noteId = $(this).data('id'); var isLocked = $(this).find('i').hasClass('fa-lock'); var titleMsg = isLocked ? "🔐 ĐỔI MẬT KHẨU MỚI:" : "🔐 CÀI ĐẶT BẢO MẬT:"; var pass = prompt(titleMsg + "\n- Nhập mật khẩu mới.\n- Để trống và bấm OK để GỠ mật khẩu.");
            if (pass !== null) { $.ajax({ type: 'POST', url: '/notes/set-password/' + noteId, data: { _token: '{{ csrf_token() }}', password: pass }, success: function(res) { if(res.success) { alert(res.message); window.location.reload(); } } }); }
        });

        $('.btn-unlock').click(function() {
            var noteId = $(this).data('id'); var pass = prompt("🔒 Ghi chú này đã được khóa. Vui lòng nhập mật khẩu để mở:");
            if (pass) { $.ajax({ type: 'POST', url: '/notes/unlock/' + noteId, data: { _token: '{{ csrf_token() }}', password: pass }, success: function(res) { if(res.success) { window.location.reload(); } else { alert(res.message); } } }); }
        });

        $('.btn-lock-now').click(function() { var noteId = $(this).data('id'); $.ajax({ type: 'POST', url: '/notes/lock/' + noteId, data: { _token: '{{ csrf_token() }}' }, success: function(res) { if(res.success) window.location.reload(); } }); });

        $('.btn-share').click(function() {
            var noteId = $(this).data('id'); var email = prompt("✉️ CHIA SẺ GHI CHÚ:\nNhập Email của người bạn muốn chia sẻ:");
            if (email) { $.ajax({ type: 'POST', url: '/notes/share/' + noteId, data: { _token: '{{ csrf_token() }}', email: email }, success: function(res) { alert(res.message); } }); }
        });

        // ====================================================
        // MÃ JS XỬ LÝ REAL-TIME COLLABORATION VÀ FIX LỖI EMOJI
        // ====================================================
        var activeEditingNoteId = null;
        var syncInterval = null;

        $('.modal').on('shown.bs.modal', function (e) {
            var formAction = $(this).find('form').attr('action');
            if(formAction && formAction.includes('/notes/')) {
                activeEditingNoteId = formAction.split('/').pop();
                
                syncInterval = setInterval(function() {
                    var titleInput = $('#editModal' + activeEditingNoteId).find('input[name="title"]');
                    var contentInput = $('#editModal' + activeEditingNoteId).find('textarea[name="content"]');
                    var statusText = $('#editModal' + activeEditingNoteId).find('.auto-save-status');
                    
                    if (!titleInput.is(':focus') && !contentInput.is(':focus')) {
                        $.ajax({
                            type: 'GET',
                            url: '/notes/data/' + activeEditingNoteId,
                            success: function(res) {
                                var changed = false;
                                
                                // Chuẩn hóa chuỗi để khắc phục lỗi so sánh Emoji và dấu xuống dòng
                                var currentTitle = (titleInput.val() || '').replace(/\r\n/g, '\n').normalize();
                                var serverTitle = (res.title || '').replace(/\r\n/g, '\n').normalize();
                                var currentContent = (contentInput.val() || '').replace(/\r\n/g, '\n').normalize();
                                var serverContent = (res.content || '').replace(/\r\n/g, '\n').normalize();
                                
                                if(currentTitle !== serverTitle) { 
                                    titleInput.val(res.title); 
                                    changed = true; 
                                }
                                if(currentContent !== serverContent) { 
                                    contentInput.val(res.content); 
                                    changed = true; 
                                }
                                
                                if(changed) {
                                    statusText.text('Đã đồng bộ thay đổi 🔄').css('color', '#1a73e8');
                                    setTimeout(function() { statusText.text(''); }, 2000);
                                }
                            }
                        });
                    }
                }, 2000);
            }
        });

        $('.modal').on('hidden.bs.modal', function (e) {
            activeEditingNoteId = null;
            clearInterval(syncInterval); 
            window.location.reload(); 
        });
    });
</script>
@endsection