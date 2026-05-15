<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Note;
use App\NoteAttachment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class NoteController extends Controller
{
    public function __construct() {
        $this->middleware('auth');
    }

    public function store(Request $request) {
        $this->validate($request, [
            'title' => 'nullable|max:191',
            'content' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $note = new Note();
        $note->user_id = Auth::id();
        $note->title = $request->title;
        $note->content = $request->input('content');
        $note->save();

        // LƯU NHÃN ĐÍNH KÈM (TIÊU CHÍ 19)
        if ($request->has('labels')) {
            $note->labels()->attach($request->labels);
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('public/notes');
            $attachment = new NoteAttachment();
            $attachment->note_id = $note->id;
            $attachment->file_path = $path;
            $attachment->save();
        }

        return redirect()->back()->with('success', 'Đã lưu ghi chú thành công!');
    }

    public function update(Request $request, $id) {
        $this->validate($request, [
            'title' => 'nullable|max:191',
            'content' => 'required',
        ]);

        $note = Note::findOrFail($id);
        $note->title = $request->input('title');
        $note->content = $request->input('content');
        $note->save();

        // CẬP NHẬT NHÃN KÈM THEO (AUTO-SAVE)
        if ($request->has('labels')) {
            $note->labels()->sync($request->labels);
        } else {
            $note->labels()->detach(); // Nếu bỏ tick hết thì gỡ sạch nhãn
        }

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back();
    }

    public function destroy($id) {
        $note = Note::findOrFail($id);
        $note->labels()->detach(); // Cắt đứt quan hệ nhãn trước khi xóa
        $note->delete();
        return redirect()->back();
    }

    public function togglePin($id) {
        $note = Note::findOrFail($id);
        $note->is_pinned = !$note->is_pinned;
        $note->save();
        return response()->json(['success' => true]);
    }

    // ==========================================
    // HÀM BẢO MẬT: CÀI/GỠ MẬT KHẨU (TIÊU CHÍ 21)
    // ==========================================
    public function setPassword(Request $request, $id) {
        // Tìm ghi chú thuộc về người dùng đang đăng nhập
        $note = Note::where('user_id', Auth::id())->findOrFail($id);
        
        $isChanging = $note->password ? true : false;

        if ($request->password) {
            // Mã hóa mật khẩu mới
            $note->password = Hash::make($request->password);
            $message = $isChanging ? 'Đã đổi mật khẩu thành công!' : 'Đã cài đặt mật khẩu thành công!';
        } else {
            // Nếu gửi dữ liệu trống -> Gỡ bỏ bảo vệ
            $note->password = null;
            $message = 'Đã gỡ bỏ mật khẩu bảo vệ!';
        }
        
        $note->save();

        // Sau khi đổi/gỡ mật khẩu, bắt buộc khóa lại để yêu cầu nhập mật khẩu mới ở lần sau
        Session::forget('unlocked_' . $note->id);
        
        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }

    // THÊM HÀM NÀY: Dùng để chủ động "Khóa lại" khi đang xem
    public function lock($id) {
        Session::forget('unlocked_' . $id);
        return response()->json(['success' => true]);
    }

    public function unlock(Request $request, $id) {
        $note = Note::findOrFail($id);
        
        // So sánh mật khẩu người dùng nhập với mật khẩu đã mã hóa trong DB
        if (Hash::check($request->password, $note->password)) {
            // Mở khóa thành công -> Cấp một "thẻ thông hành" lưu vào Session
            Session::put('unlocked_' . $note->id, true);
            return response()->json(['success' => true]);
        }
        
        return response()->json(['success' => false, 'message' => 'Mật khẩu không chính xác!']);
    }

    // ==========================================
    // CHIA SẺ GHI CHÚ (TIÊU CHÍ 23)
    // ==========================================
    public function share(Request $request, $id) {
        $note = Note::where('user_id', Auth::id())->findOrFail($id);
        
        // Tìm người dùng qua email
        $receiver = \App\User::where('email', $request->email)->first();

        if (!$receiver) {
            return response()->json(['success' => false, 'message' => '❌ Không tìm thấy người dùng với Email này!']);
        }
        if ($receiver->id == $note->user_id) {
            return response()->json(['success' => false, 'message' => '⚠️ Bạn không thể tự chia sẻ cho chính mình!']);
        }
        if ($note->sharedUsers->contains($receiver->id)) {
            return response()->json(['success' => false, 'message' => '⚠️ Ghi chú này đã được chia sẻ cho người này rồi!']);
        }

        // Thực hiện kết nối
        $note->sharedUsers()->attach($receiver->id);
        return response()->json(['success' => true, 'message' => '✅ Đã chia sẻ ghi chú thành công!']);
    }

    // ==========================================
    // API LẤY DỮ LIỆU REAL-TIME (TIÊU CHÍ 24)
    // ==========================================
    public function getNoteData($id) {
        $note = Note::findOrFail($id);
        return response()->json([
            'title' => $note->title,
            'content' => $note->content
        ]);
    }

    // ==========================================
    // TỪ CHỐI/XÓA GHI CHÚ ĐƯỢC CHIA SẺ
    // ==========================================
    public function removeShared($id) {
        // Thay vì dùng Auth::user(), gọi trực tiếp Model User để VS Code hiểu được hàm sharedNotes()
        $user = \App\User::find(Auth::id());
        
        // Chỉ dùng lệnh detach() để xóa liên kết trong bảng trung gian note_user
        // Hoàn toàn KHÔNG đụng chạm đến dữ liệu gốc trong bảng notes
        $user->sharedNotes()->detach($id);
        
        return redirect()->back();
    }
}