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
        Schema::create('thing_stats', function (Blueprint $table) {
            $table->id();

            $table->foreignId('stat_thing_id')
                ->nullable(false)
                ->comment("The thing that has the these stats")
                ->index()
                ->constrained('things')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->integer('stat_data_byte_rows')->default(0)->nullable(false)
                ->comment('The current count of the sum of the bytes * rows in db of the data, for this thing');



            $table->integer('stat_back_offs_done')->nullable(false)->default(0)
                ->comment('how many backoffs have been done for this thing');

            $table->integer('stat_limit_data_byte_rows')->default(0)->nullable(false)
                ->comment('When stat_data_byte_rows exceeds this, will trigger stat_backoff_data_policy');




            $table->integer('stat_limit_descendants')->default(0)->nullable(false)
                ->comment('The limit of all the descendants, if exceeded then building will be flag as paused for resources');

            $table->integer('stat_backoff_data_policy')->nullable()->default(null)
                ->comment('the policy to use when data is over');




            $table->timestamps();

            $table->jsonb('stat_constant_data')
                ->nullable()->default(null)
                ->comment("This is this thing constant data merged with the parent of the thing constant data with these keys winning in conflict");



        });


        DB::statement("ALTER TABLE thing_stats ALTER COLUMN created_at SET DEFAULT NOW();");

        DB::statement("
            CREATE TRIGGER update_modified_time BEFORE UPDATE ON thing_stats FOR EACH ROW EXECUTE PROCEDURE update_modified_column();
        ");



    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thing_stats');
    }
};
