<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

            $table->uuid('ref_uuid')
                ->unique()
                ->nullable(false)
                ->comment("used for display and id outside the code");




            $table->text('thing_error_file')->default(null)->nullable()
                ->comment('The file of the exception');

            $table->jsonb('related_tags')
                ->nullable()->default(null)
                ->comment("array of string tags from the thing or hook");

            $table->text('thing_error_message')->default(null)->nullable()
                ->comment('the message');

            $table->jsonb('thing_error_trace')->default(null)->nullable()
                ->comment('the stack trace');

            $table->jsonb('thing_previous_errors')->default(null)->nullable()
                ->comment('Previous error info goes here');

            $table->string('thing_code_version',20)->default(null)->nullable()
                ->comment('The version of the thing code');

            $table->string('hbc_version',20)->default(null)->nullable()
                ->comment('The version of the hexbatch code');

        });

        DB::statement('ALTER TABLE thing_errors ALTER COLUMN ref_uuid SET DEFAULT uuid_generate_v4();');

        DB::statement("ALTER TABLE thing_errors ALTER COLUMN created_at SET DEFAULT NOW();");

        DB::statement("
            CREATE TRIGGER update_modified_time BEFORE UPDATE ON thing_errors FOR EACH ROW EXECUTE PROCEDURE update_modified_column();
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thing_errors');
    }
};
