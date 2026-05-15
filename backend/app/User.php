<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable 
{
    use Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'avatar', 'role', 'preferences', 'api_token', 'is_active', 'activation_token',
    ];

    protected $hidden = [
        'password', 'remember_token', 'api_token',
    ];

    public function sharedNotes() {
        return $this->belongsToMany('App\Note', 'shared_notes', 'shared_with_user_id', 'note_id')
            ->withPivot('id', 'permission', 'shared_by_user_id')
            ->withTimestamps();
    }
}
