<?php

use Hexbatch\Things\Controllers\ThingController;
use Illuminate\Support\Facades\Route;


Route::prefix('hbc-things')->group(function () {

    //unprotected go here

    $hbc_middleware = [];
    $my_auth = config('hbc-things.auth_middleware_alias');
    if ($my_auth) {
        $hbc_middleware[] =  $my_auth;
    }
    Route::middleware($hbc_middleware)->group(function () {
        Route::get('list', [ThingController::class, 'thing_list'])->name('core.things.list');

        Route::prefix('{thing}')->group(function () {

            Route::get('show', [ThingController::class, 'thing_show'])->name('core.things.show');
            Route::get('inspect', [ThingController::class, 'thing_inspect'])->name('core.things.inspect');
            Route::delete('trim', [ThingController::class, 'thing_trim_tree'])->name('core.things.trim');



            Route::prefix('debugging')->group(function () {
                Route::post('breakpoint', [ThingController::class, 'thing_add_breakpoint'])->name('core.things.debugging.breakpoint');
                Route::delete('clear', [ThingController::class, 'thing_clear_breakpoint'])->name('core.things.debugging.clear_breakpoint');
                Route::post('run', [ThingController::class, 'thing_run'])->name('core.things.debugging.run');
                Route::post('single_step', [ThingController::class, 'thing_single_step'])->name('core.things.debugging.single_step');
                Route::patch('pause', [ThingController::class, 'thing_pause'])->name('core.things.debugging.pause');
                Route::patch('unpause', [ThingController::class, 'thing_unpause'])->name('core.things.debugging.unpause');
            });

            Route::prefix('rates')->group(function () {

                Route::post('apply', [ThingController::class, 'thing_rate_apply'])->name('core.things.rates.apply');
                Route::get('list', [ThingController::class, 'thing_rate_list'])->name('core.things.rates.list');

                Route::prefix('{thing_setting}')->group(function () {
                    Route::middleware(Middleware\ValidateThingSettingOwnership::class)->group(function () {
                        Route::delete('remove', [ThingController::class, 'thing_rate_remove'])->name('core.things.rates.remove');
                        Route::get('show', [ThingController::class, 'thing_rate_show'])->name('core.things.rates.show');
                        Route::post('edit', [ThingController::class, 'thing_rate_edit'])->name('core.things.rates.edit');
                    });
                });
            });

            Route::prefix('hooks')->group(function () {
                Route::get('list', [ThingController::class, 'thing_hook_list'])->name('core.things.hooks.list');
                Route::post('create', [ThingController::class, 'thing_hook_create'])->name('core.things.hooks.create');


                Route::prefix('{thing_hook}')->group(function () {
                    Route::middleware(Middleware\ValidateThingHookOwnership::class)->group(function () {
                        Route::get('show', [ThingController::class, 'thing_hook_show'])->name('core.things.hooks.show');
                        Route::patch('edit', [ThingController::class, 'thing_hook_edit'])->name('core.things.hooks.edit');
                        Route::delete('destroy', [ThingController::class, 'thing_hook_destroy'])->name('core.things.hooks.destroy');
                    });
                });
            });
        });
    });
});


