<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("DROP FUNCTION IF EXISTS update_modified_column();");

        DB::statement("DROP TYPE IF EXISTS type_of_thing_status;");
        DB::statement("DROP TYPE IF EXISTS type_of_thing_hook_mode;");
        DB::statement("DROP TYPE IF EXISTS type_of_thing_callback;");
        DB::statement("DROP TYPE IF EXISTS type_of_thing_callback_status;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //does nothing going down
    }
};
