<?php

use Hexbatch\Things\Controllers\ThingController;
use Illuminate\Support\Facades\Route;


Route::prefix('hbc-things')->group(function () {



    $hbc_middleware = [];
    $my_auth = config('hbc-things.auth_middleware_alias'); //this decides if the owner type/id is valid
    if ($my_auth) {
        $hbc_middleware[] =  $my_auth;
    }
    Route::middleware($hbc_middleware)->prefix('{owner_type}/{owner_id}')->group(function () {
        Route::get('list_things', [ThingController::class, 'thing_list'])->name('core.things.list');

        Route::prefix('thing/{thing}')->group(function () {

            Route::get('show', [ThingController::class, 'thing_show'])->name('core.things.show');
            Route::get('inspect', [ThingController::class, 'thing_inspect'])->name('core.things.inspect');
            Route::put('shortcut', [ThingController::class, 'thing_shortcut'])->name('core.things.shortcut');



            Route::prefix('setting')->group(function () {

                Route::post('create', [ThingController::class, 'thing_setting_create'])->name('core.things.rates.apply');
                Route::get('list', [ThingController::class, 'thing_setting_list'])->name('core.things.rates.list');

                Route::prefix('{thing_setting}')->group(function () {
                    Route::delete('remove', [ThingController::class, 'thing_setting_remove'])->name('core.things.rates.remove');
                    Route::get('show', [ThingController::class, 'thing_setting_show'])->name('core.things.rates.show');
                });
            });

            Route::prefix('callback/{thing_callback}')->group(function () {

                Route::post('answer', [ThingController::class, 'thing_callback_create'])->name('core.things.rates.apply');
                Route::get('show', [ThingController::class, 'thing_callback_show'])->name('core.things.rates.list');

            });

        });

        Route::prefix('thing_hook')->group(function () {
            Route::get('list', [ThingController::class, 'thing_hook_list'])->name('core.things.hooks.list');
            Route::post('create', [ThingController::class, 'thing_hook_create'])->name('core.things.hooks.create');

            Route::prefix('{thing_hook}')->group(function () {
                Route::get('show', [ThingController::class, 'thing_hook_show'])->name('core.things.hooks.show');
                Route::patch('edit', [ThingController::class, 'thing_hook_edit'])->name('core.things.hooks.edit');
                Route::delete('destroy', [ThingController::class, 'thing_hook_destroy'])->name('core.things.hooks.destroy');
            });

        });

    });
});


