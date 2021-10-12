<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiscussionVoteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('discussion_vote', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('discussion_id');
            $table->foreign('discussion_id')->references('id')->on('discussion');
            $table->unsignedBigInteger('participant_id');
            $table->foreign('participant_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->dateTime('date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('discussion_vote');
    }
}
