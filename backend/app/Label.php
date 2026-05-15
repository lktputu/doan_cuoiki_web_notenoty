<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Label extends Model
{
    // Chỉ định rõ tên bảng trong Database
    protected $table = 'labels';

    // Khai báo các cột được phép thêm dữ liệu
    protected $fillable = ['user_id', 'name'];

    public function notes()
    {
        // Nhãn này thuộc về nhiều Ghi chú
        return $this->belongsToMany('App\Note', 'label_note', 'label_id', 'note_id');
    }
}
