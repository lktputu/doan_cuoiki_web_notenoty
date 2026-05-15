<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Label;
use Illuminate\Support\Facades\Auth; // <-- Đã gọi tên đầy đủ ở đây để fix lỗi gạch đỏ

class LabelController extends Controller
{
    public function __construct() {
        $this->middleware('auth');
    }

    // Thêm nhãn mới
    public function store(Request $request) {
        $this->validate($request, ['name' => 'required|max:50']);
        
        $label = new Label();
        $label->name = $request->name;
        $label->user_id = Auth::id();
        $label->save();

        return redirect()->back()->with('success', 'Đã thêm nhãn!');
    }

    // Sửa tên nhãn
    public function update(Request $request, $id) {
        $this->validate($request, ['name' => 'required|max:50']);
        
        $label = Label::where('user_id', Auth::id())->findOrFail($id);
        $label->name = $request->name;
        $label->save();

        return redirect()->back();
    }

    // Xóa nhãn
    public function destroy($id) {
        $label = Label::where('user_id', Auth::id())->findOrFail($id);
        $label->delete();

        return redirect()->back();
    }
}