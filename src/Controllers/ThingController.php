<?php

namespace Hexbatch\Things\Controllers;

use Hexbatch\Things\Models\Thing;
use Hexbatch\Things\Models\ThingCallback;
use Hexbatch\Things\Models\ThingHook;
use Hexbatch\Things\Models\ThingSetting;
use Symfony\Component\HttpFoundation\Response as CodeOf;
use OpenApi\Attributes as OA;

class ThingController  {


    #[OA\Get(
        path: '/hbc-things/v1/{owner_type}/{owner_id}/hooks/list',
        operationId: 'hbc-things.hooks.list',
        description: "",
        summary: 'List all the hooks registered to this owner',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_hook_list(string $owner_type,int $owner_id) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

    #[OA\Post(
        path: '/hbc-things/v1/{owner_type}/{owner_id}/hooks/create',
        operationId: 'hbc-things.hooks.create',
        description: "",
        summary: 'Create a new hook',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_hook_create(string $owner_type,int $owner_id) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }


    #[OA\Get(
        path: '/hbc-things/v1/{owner_type}/{owner_id}/hooks/{hook_uuid}/show',
        operationId: 'hbc-things.hooks.show',
        description: "",
        summary: 'Shows information about a hook',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_hook_show(string $owner_type,int $owner_id,ThingHook $hook) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

    #[OA\Patch(
        path: '/hbc-things/v1/{owner_type}/{owner_id}/hooks/{hook_uuid}/edit',
        operationId: 'hbc-things.hooks.edit',
        description: "Affects future hooks",
        summary: 'Edits a hook',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_hook_edit(string $owner_type,int $owner_id,ThingHook $hook) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }


    #[OA\Delete(
        path: '/hbc-things/v1/{owner_type}/{owner_id}/hooks/{hook_uuid}/destroy',
        operationId: 'hbc-things.hooks.destroy',
        description: "",
        summary: 'Edits a hook',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_hook_destroy(string $owner_type,int $owner_id,ThingHook $hook) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

















    #[OA\Get(
        path: '/hbc-things/v1/{owner_type}/{owner_id}/things/list',
        operationId: 'hbc-things.things.list',
        description: "Shows tree status, times, progress",
        summary: 'Lists top things that are owned by user',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_list(string $owner_type,int $owner_id) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

    #[OA\Get(
        path: '/hbc-things/v1/{owner_type}/{owner_id}/things/{thing_uuid}/show',
        operationId: 'hbc-things.things.show',
        description: "Lesser detail in decendants",
        summary: 'Shows a thing and its descendants',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_show(string $owner_type,int $owner_id,Thing $thing) { //  (a tree)
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }


    #[OA\Get(
        path: '/hbc-things/v1/{owner_type}/{owner_id}/things/{thing_uuid}/inspect',
        operationId: 'hbc-things.things.inspect',
        description: "",
        summary: 'Inspects a single thing',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_inspect(string $owner_type,int $owner_id,Thing $thing) { //  (a single thing)
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }




    #[OA\Put(
        path: '/hbc-things/v1/{owner_type}/{owner_id}/things/{thing_uuid}/shortcut',
        operationId: 'hbc-things.things.shortcut',
        description: "If children not run they are shortcut too",
        summary: 'Shortcuts a thing',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_shortcut(string $owner_type,int $owner_id,Thing $thing) {  //(if child will return false to parent when it runs, if root then its just gone)
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }


    #[OA\Post(
        path: '/hbc-things/v1/{owner_type}/{owner_id}/settings/create',
        operationId: 'hbc-things.settings.create',
        description: "Will apply it to current and new",
        summary: 'Creates a setting',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_setting_create(string $owner_type,int $owner_id) {
        //get all setting values from body
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

    #[OA\Delete(
        path: '/hbc-things/v1/{owner_type}/{owner_id}/things/rates/remove',
        operationId: 'hbc-things.things.rates.remove',
        description: "",
        summary: 'Removes a rate(s) to a thing and its descendants',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_setting_remove(string $owner_type,int $owner_id) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

    #[OA\Put(
        path: '/hbc-things/v1/{owner_type}/{owner_id}/things/rates/remove',
        operationId: 'hbc-things.things.rates.remove',
        description: "",
        summary: 'Removes a rate(s) to a thing and its descendants',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_setting_edit(string $owner_type,int $owner_id) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }


    #[OA\Get(
        path: '/hbc-things/v1/{owner_type}/{owner_id}/settings/list',
        operationId: 'hbc-things.settings.list.mine',
        description: "",
        summary: 'Lists the settings for me',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_setting_list_mine(string $owner_type, int $owner_id) { //also search
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

    #[OA\Get(
        path: '/hbc-things/v1/{owner_type}/{owner_id}/settings/list/other/{other_type}/{other_id}',
        operationId: 'hbc-things.settings.list.other',
        description: "",
        summary: 'Lists settings applied to another user',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_setting_list_other(string $owner_type,int $owner_id,string $other_type,int $other_id) { //also search
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

    #[OA\Get(
        path: '/hbc-things/v1/{owner_type}/{owner_id}/settings/list/other/{action_type}/{action_id}',
        operationId: 'hbc-things.settings.list.action',
        description: "",
        summary: 'Lists settings about this action',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_setting_list_action(string $owner_type,int $owner_id,string $action_type,int $action_id) { //also search
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }


    #[OA\Get(
        path: '/hbc-things/v1/{owner_type}/{owner_id}/settings/{setting_uuid}/rates/show',
        operationId: 'hbc-things.settings.show',
        description: "",
        summary: 'Shows information about a setting',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_setting_show(string $owner_type,int $owner_id,ThingSetting $setting) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

    #[OA\Get(
        path: '/hbc-things/v1/{owner_type}/{owner_id}/callbacks/{callback_uuid}/show',
        operationId: 'hbc-things.callbacks.show',
        description: "",
        summary: 'Show a callback',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_callback_show(string $owner_type, int $owner_id,ThingCallback $callback) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

    #[OA\Post(
        path: '/hbc-things/v1/{owner_type}/{owner_id}/callbacks/{callback_uuid}/answer',
        operationId: 'hbc-things.callbacks.answer',
        description: "",
        summary: 'Complete a callback',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_callback_answer(string $owner_type, int $owner_id,ThingCallback $callback) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }


}
