<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiscussionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('discussion', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('club_id');
            $table->unsignedBigInteger('creator_id');
            $table->string('topic');
            $table->text('description');
            $table->string('description_audio');
            $table->string('participants');
            $table->string('comment');
            $table->string('vote');
            $table->string('tags');
            $table->string('status');
            $table->dateTime('date');
            $table->foreign('club_id')->references('id')->on('club');
            $table->foreign('creator_id')->references('id')->on('users');

            //$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('discussion');
    }
}
