<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSharedNotesTable extends Migration {
    public function up() {
        Schema::create('shared_notes', function (Blueprint $table) {
            $table->increments('id');
            
            $table->integer('note_id')->unsigned();
            $table->foreign('note_id')->references('id')->on('notes')->onDelete('cascade');
            
            $table->integer('shared_with_user_id')->unsigned();
            $table->foreign('shared_with_user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->enum('permission', ['view', 'edit'])->default('view');
            $table->timestamps();
            $table->unique(['note_id', 'shared_with_user_id']);
        });
    }

    public function down() {
        Schema::dropIfExists('shared_notes');
    }
}