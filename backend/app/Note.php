<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'content',
        'color',
        'is_pinned',
        'pinned_at',
        'password',
        'locked_by',
        'locked_at',
    ];

    public function attachments()
    {
        return $this->hasMany('App\NoteAttachment', 'note_id');
    }

    public function labels()
    {
        return $this->belongsToMany('App\Label', 'label_note', 'note_id', 'label_id');
    }

    public function sharedUsers()
    {
        return $this->belongsToMany('App\User', 'shared_notes', 'note_id', 'shared_with_user_id')
            ->withPivot('id', 'permission', 'shared_by_user_id')
            ->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id');
    }
}
