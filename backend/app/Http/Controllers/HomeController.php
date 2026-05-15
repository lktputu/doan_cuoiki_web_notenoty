<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Note; // Gọi Model Note ra để làm việc với DB
use Illuminate\Support\Facades\Auth; // Gọi thư viện kiểm tra đăng nhập

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $user_id = \Illuminate\Support\Facades\Auth::id();
        $labels = \App\Label::where('user_id', $user_id)->get();
        
        // KIỂM TRA: CÓ PHẢI ĐANG Ở TRANG "ĐƯỢC CHIA SẺ"?
        $isSharedPage = $request->has('shared');

        if ($isSharedPage) {
            // Lấy các ghi chú người khác gửi cho mình
            $user = \App\User::find($user_id);
            $notes = $user->sharedNotes()->with('labels', 'attachments')->orderBy('created_at', 'desc')->get();
        } else {
            // Lấy các ghi chú của chính mình (Logic cũ)
            $query = \App\Note::with('labels')->where('user_id', $user_id);
            
            if ($request->has('search') && $request->search != '') {
                $keyword = $request->search;
                $query->where(function($q) use ($keyword) {
                    $q->where('title', 'LIKE', '%' . $keyword . '%')->orWhere('content', 'LIKE', '%' . $keyword . '%');
                });
            }
            if ($request->has('label_id') && $request->label_id != '') {
                $label_id = $request->label_id;
                $query->whereHas('labels', function($q) use ($label_id) {
                    $q->where('labels.id', $label_id);
                });
            }
            $notes = $query->orderBy('is_pinned', 'desc')->orderBy('created_at', 'desc')->get();
        }

        // Truyền thêm biến $isSharedPage ra giao diện để ẩn/hiện nút Sửa, Xóa
        return view('home', compact('notes', 'labels', 'isSharedPage'));

    }

    // ==========================================
    // HÀM HIỂN THỊ HỒ SƠ CÁ NHÂN (TIÊU CHÍ 5)
    // ==========================================
    public function profile()
    {
        $user = \App\User::find(\Illuminate\Support\Facades\Auth::id());
        
        // Thống kê sương sương cho giao diện thêm ngầu
        $noteCount = \App\Note::where('user_id', $user->id)->count();
        $sharedCount = $user->sharedNotes()->count();

        return view('profile', compact('user', 'noteCount', 'sharedCount'));
    }

    // ==========================================
    // HÀM XỬ LÝ CẬP NHẬT HỒ SƠ & AVATAR (TIÊU CHÍ 6)
    // ==========================================
    public function updateProfile(\Illuminate\Http\Request $request)
    {
        $user = \App\User::find(\Illuminate\Support\Facades\Auth::id());

        // ĐÃ SỬA LỖI PHIÊN BẢN: Dùng $this->validate thay vì $request->validate
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
        ]);

        $user->name = $request->name;

        // Nếu người dùng có chọn upload file ảnh mới
        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');
            // Đổi tên file cho khỏi trùng (dùng hàm time)
            $filename = time() . '.' . $avatar->getClientOriginalExtension();
            // Đưa ảnh vào thư mục public/uploads/avatars
            $avatar->move(public_path('uploads/avatars'), $filename);
            
            $user->avatar = $filename; // Lưu tên ảnh vào Database
        }

        $user->save();

        return redirect()->back()->with('success', 'Tuyệt vời! Hồ sơ của bạn đã được cập nhật.');
    }

    // ==========================================
    // HÀM XỬ LÝ ĐỔI MẬT KHẨU (TIÊU CHÍ 7)
    // ==========================================
    public function changePassword(\Illuminate\Http\Request $request)
    {
        // 1. Kiểm tra dữ liệu nhập vào
        $this->validate($request, [
            'current_password' => 'required',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user = \App\User::find(\Illuminate\Support\Facades\Auth::id());

        // 2. Kiểm tra Mật khẩu hiện tại có gõ đúng không?
        if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
            return redirect()->back()->withErrors(['current_password' => 'Mật khẩu hiện tại không chính xác!']);
        }

        // 3. Kiểm tra Mật khẩu mới có bị trùng mật khẩu cũ không?
        if (\Illuminate\Support\Facades\Hash::check($request->new_password, $user->password)) {
            return redirect()->back()->withErrors(['new_password' => 'Mật khẩu mới không được trùng với mật khẩu hiện tại!']);
        }

        // 4. Nếu vượt qua hết các bài kiểm tra -> Lưu mật khẩu mới
        $user->password = bcrypt($request->new_password);
        $user->save();

        return redirect()->back()->with('success', 'Đổi mật khẩu thành công! Hãy ghi nhớ mật khẩu mới nhé.');
    }

    // ==========================================
    // HÀM XỬ LÝ LƯU CÀI ĐẶT CÁ NHÂN (TIÊU CHÍ 8)
    // ==========================================
    public function updatePreferences(\Illuminate\Http\Request $request)
    {
        $user = \App\User::find(\Illuminate\Support\Facades\Auth::id());
        
        // Nếu công tắc được bật, nó sẽ gửi giá trị '1', ngược lại là '0'
        $user->dark_mode = $request->has('dark_mode') ? 1 : 0;
        $user->save();

        return redirect()->back()->with('success', 'Đã lưu Cài đặt cá nhân thành công!');
    }
}