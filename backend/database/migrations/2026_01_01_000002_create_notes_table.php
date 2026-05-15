<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotesTable extends Migration {
    public function up() {
        Schema::create('notes', function (Blueprint $table) {
            $table->increments('id');
            
            // Cú pháp khóa ngoại chuẩn cũ
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->string('password')->nullable(); 
            
            // Khóa ngoại cho người đang lock ghi chú
            $table->integer('locked_by')->unsigned()->nullable();
            $table->foreign('locked_by')->references('id')->on('users')->onDelete('set null');
            
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('notes');
    }
}