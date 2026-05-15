<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NoteAttachment extends Model
{
    protected $fillable = ['note_id', 'file_path'];

    public function note()
    {
        return $this->belongsTo('App\Note', 'note_id');
    }
}
