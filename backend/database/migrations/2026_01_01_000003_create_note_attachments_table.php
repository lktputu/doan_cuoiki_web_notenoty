<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNoteAttachmentsTable extends Migration {
    public function up() {
        Schema::create('note_attachments', function (Blueprint $table) {
            $table->increments('id');
            
            $table->integer('note_id')->unsigned();
            $table->foreign('note_id')->references('id')->on('notes')->onDelete('cascade');
            
            $table->string('file_path'); 
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('note_attachments');
    }
}