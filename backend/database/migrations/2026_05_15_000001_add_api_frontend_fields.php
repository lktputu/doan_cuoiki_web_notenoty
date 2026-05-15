<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApiFrontendFields extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'api_token')) {
                $table->string('api_token', 80)->nullable()->unique()->after('remember_token');
            }

            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->nullable()->after('avatar');
            }

            if (!Schema::hasColumn('users', 'preferences')) {
                $table->text('preferences')->nullable()->after('view_preference');
            }
        });

        Schema::table('notes', function (Blueprint $table) {
            if (!Schema::hasColumn('notes', 'color')) {
                $table->string('color', 30)->default('nc-lav')->after('content');
            }

            if (!Schema::hasColumn('notes', 'pinned_at')) {
                $table->timestamp('pinned_at')->nullable()->after('is_pinned');
            }
        });

        Schema::table('shared_notes', function (Blueprint $table) {
            if (!Schema::hasColumn('shared_notes', 'shared_by_user_id')) {
                $table->integer('shared_by_user_id')->unsigned()->nullable()->after('note_id');
                $table->foreign('shared_by_user_id')->references('id')->on('users')->onDelete('cascade');
            }
        });
    }

    public function down()
    {
        Schema::table('shared_notes', function (Blueprint $table) {
            if (Schema::hasColumn('shared_notes', 'shared_by_user_id')) {
                $table->dropForeign(['shared_by_user_id']);
                $table->dropColumn('shared_by_user_id');
            }
        });

        Schema::table('notes', function (Blueprint $table) {
            if (Schema::hasColumn('notes', 'pinned_at')) {
                $table->dropColumn('pinned_at');
            }

            if (Schema::hasColumn('notes', 'color')) {
                $table->dropColumn('color');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'preferences')) {
                $table->dropColumn('preferences');
            }

            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }

            if (Schema::hasColumn('users', 'api_token')) {
                $table->dropUnique(['api_token']);
                $table->dropColumn('api_token');
            }
        });
    }
}
