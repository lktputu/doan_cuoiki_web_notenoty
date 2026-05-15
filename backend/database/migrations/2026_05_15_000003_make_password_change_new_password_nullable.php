<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MakePasswordChangeNewPasswordNullable extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE password_change_requests MODIFY new_password VARCHAR(255) NULL');
    }

    public function down()
    {
        DB::table('password_change_requests')->whereNull('new_password')->delete();
        DB::statement('ALTER TABLE password_change_requests MODIFY new_password VARCHAR(255) NOT NULL');
    }
}
