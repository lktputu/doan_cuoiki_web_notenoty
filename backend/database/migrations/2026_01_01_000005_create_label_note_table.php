<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLabelNoteTable extends Migration {
    public function up() {
        Schema::create('label_note', function (Blueprint $table) {
            $table->integer('note_id')->unsigned();
            $table->foreign('note_id')->references('id')->on('notes')->onDelete('cascade');
            
            $table->integer('label_id')->unsigned();
            $table->foreign('label_id')->references('id')->on('labels')->onDelete('cascade');
            
            $table->primary(['note_id', 'label_id']); 
        });
    }

    public function down() {
        Schema::dropIfExists('label_note');
    }
}