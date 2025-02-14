<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('thing_errors', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->integer('thing_error_code')->default(null)->nullable()
                ->comment('The code of the exception');

            $table->integer('thing_error_line')->default(null)->nullable()
                ->comment('The line of the exception');

            $table->float('thing_code_version')->default(null)->nullable()
                ->comment('The version of the thing code');

            $table->float('hbc_version')->default(null)->nullable()
                ->comment('The version of the hexbatch code');

            $table->text('thing_error_message')->default(null)->nullable()
                ->comment('the message');

            $table->jsonb('thing_error_trace')->default(null)->nullable()
                ->comment('the stack trace');

            $table->string('thing_error_file')->default(null)->nullable()
                ->comment('The file of the exception');


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thing_errors');
    }
};
