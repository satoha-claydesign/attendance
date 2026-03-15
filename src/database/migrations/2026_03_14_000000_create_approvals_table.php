<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApprovalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('timestamp_id')->nullable()->index();
            $table->string('name')->nullable();
            $table->date('target_date');
            $table->string('status')->default('pending');
            $table->text('reason')->nullable();
            $table->json('payload')->nullable();
            $table->string('details_link')->nullable();
            $table->timestamps();

            // optional foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('timestamp_id')->references('id')->on('timestamps')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('approvals');
    }
}
