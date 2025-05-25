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
        Schema::table('things', function (Blueprint $table) {

            $table->string('action_type',80)
                ->nullable()->default(null)
                ->comment("The type of action")
                ->change();

            $table->string('owner_type',80)
                ->nullable()->default(null)
                ->comment("The type of owner")
                ->change();

            $table->timestamp('thing_wait_until_at')->nullable()->default(null)
                ->index()
                ->after('thing_ran_at')
                ->comment('if set, then this thing is waiting until the time, when it can be woken up. Ignored if thing not waiting');

        });


        Schema::table('thing_hooks', function (Blueprint $table) {

            $table->string('action_type',80)
                ->nullable()->default(null)
                ->comment("Hooks can filter on a specific action")
                ->change();

            $table->string('owner_type',80)
                ->nullable()->default(null)
                ->comment("The type of owner")
                ->change();

            $table->string('filter_owner_type',80)
                ->nullable()->default(null)
                ->comment("Hooks can filter on an owner type")
                ->change();

        });


        Schema::table('thing_callbacks', function (Blueprint $table) {
            $table->timestamp('wait_in_seconds')->nullable()->default(null)
                ->after('callback_run_at')
                ->comment('This is the advice given to wait, from the callback, to the thing');

            $table->boolean('is_halting_thing_stack')
                ->nullable(false)->default(0)
                ->after('is_signalling_when_done')
                ->comment("if true, then the process chain stops after this only if hook is blocking");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('things', function (Blueprint $table) {
            $table->string('action_type',30)
                ->nullable()->default(null)
                ->comment("The type of action")
                ->change();

            $table->string('owner_type',30)
                ->nullable()->default(null)
                ->comment("The type of owner")
                ->change();

            $table->dropColumn('thing_wait_until_at');
        });


        Schema::table('thing_hooks', function (Blueprint $table) {
            $table->string('action_type',30)
                ->nullable()->default(null)
                ->comment("Hooks can filter on a specific action")
                ->change();

            $table->string('owner_type',30)
                ->nullable()->default(null)
                ->comment("The type of owner")
                ->change();

            $table->string('filter_owner_type',30)
                ->nullable()->default(null)
                ->comment("Hooks can filter on an owner type")
                ->change();
        });

        Schema::table('thing_callbacks', function (Blueprint $table) {
            $table->dropColumn('wait_in_seconds');
            $table->dropColumn('is_halting_thing_stack');
        });
    }
};
