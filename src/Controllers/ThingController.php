<?php

namespace Hexbatch\Things\Controllers;

use App\OpenApi\ErrorResponse;
use Hexbatch\Things\Enums\TypeOfCallbackStatus;
use Hexbatch\Things\Enums\TypeOfOwnerGroup;
use Hexbatch\Things\Enums\TypeOfThingStatus;
use Hexbatch\Things\Exceptions\HbcThingException;
use Hexbatch\Things\Interfaces\IThingOwner;
use Hexbatch\Things\Models\Thing;
use Hexbatch\Things\Models\ThingCallback;
use Hexbatch\Things\Models\ThingHook;
use Hexbatch\Things\OpenApi\Callbacks\CallbackCollectionResponse;
use Hexbatch\Things\OpenApi\Callbacks\CallbackResponse;
use Hexbatch\Things\OpenApi\Callbacks\CallbackSearchParams;
use Hexbatch\Things\OpenApi\Callbacks\ManualParams;
use Hexbatch\Things\OpenApi\Hooks\HookCollectionResponse;
use Hexbatch\Things\OpenApi\Hooks\HookParams;
use Hexbatch\Things\OpenApi\Hooks\HookResponse;
use Hexbatch\Things\OpenApi\Hooks\HookSearchParams;
use Hexbatch\Things\OpenApi\Things\ThingCollectionResponse;
use Hexbatch\Things\OpenApi\Things\ThingResponse;
use Hexbatch\Things\OpenApi\Things\ThingSearchParams;
use Hexbatch\Things\Requests\CallbackSearchRequest;
use Hexbatch\Things\Requests\HookRequest;
use Hexbatch\Things\Requests\HookSearchRequest;
use Hexbatch\Things\Requests\ManualFillRequest;
use Hexbatch\Things\Requests\ThingSearchRequest;
use OpenApi\Attributes as OA;
use OpenApi\Attributes\JsonContent;
use Symfony\Component\HttpFoundation\Response as CodeOf;

class ThingController  {

    #[OA\Get(
        path: '/api/hbc-things/v1/hooks/list',
        operationId: 'hbc-things.hooks.list',
        description: "",
        summary: 'List all the hooks registered to this owner',
        security: [['bearerAuth' => []]],
        tags: ['hook'],
        parameters: [new OA\QueryParameter( name: 'Search params', description: "Optionally search", in: 'query',
            allowEmptyValue: true, schema: new OA\Schema( ref: HookSearchParams::class) )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_OK, description: 'The hook list',content: new JsonContent(ref: HookCollectionResponse::class)),
            new OA\Response( response: CodeOf::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation fails', content: new JsonContent(ref: ErrorResponse::class)),
            new OA\Response( response: CodeOf::HTTP_BAD_REQUEST, description: 'When not logged in',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_BAD_REQUEST,"message"=>"Unauthenticated."])),
        ]
    )]
    public function hook_list(IThingOwner $owner,HookSearchRequest $request) {

        $search = HookSearchParams::fromRequest(request:$request);
        $hooks = ThingHook::buildHook(hook_owner_group: $owner, hook_group_hint: TypeOfOwnerGroup::HOOK_LIST,params: $search)
            ->orderBy('id','desc')
            ->cursorPaginate();
        return response()->json(new HookCollectionResponse(given_hooks: $hooks), CodeOf::HTTP_OK);
    }


    #[OA\Get(
        path: '/api/hbc-things/v1/hooks/admin/list',
        operationId: 'hbc-things.hooks.admin.list',
        description: "",
        summary: 'List all the hooks',
        security: [['bearerAuth' => []]],
        tags: ['hook','admin'],
        parameters: [new OA\QueryParameter( name: 'Search params', description: "Optionally search", in: 'query',
            allowEmptyValue: true, schema: new OA\Schema( ref: HookSearchParams::class) )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_OK, description: 'The hook list',content: new JsonContent(ref: HookCollectionResponse::class)),
            new OA\Response( response: CodeOf::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation fails', content: new JsonContent(ref: ErrorResponse::class)),
            new OA\Response( response: CodeOf::HTTP_BAD_REQUEST, description: 'When not logged in',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_BAD_REQUEST,"message"=>"Unauthenticated."])),
            new OA\Response( response: CodeOf::HTTP_FORBIDDEN, description: 'When not admin',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_FORBIDDEN,"message"=>"Not an admin."]))
        ]
    )]
    public function admin_hook_list(HookSearchRequest $request) {

        $search = HookSearchParams::fromRequest(request:$request);
        /** @var ThingHook[] $hooks */
        $hooks = ThingHook::buildHook(params: $search)->orderBy('id','desc')->cursorPaginate();
        return response()->json(new HookCollectionResponse(given_hooks: $hooks), CodeOf::HTTP_OK);
    }

    #[OA\Delete(
        path: '/api/hbc-things/v1/hooks/admin/{thing_hook}/destroy',
        operationId: 'hbc-things.hooks.admin.destroy',
        description: "",
        summary: 'Removes a hook',
        security: [['bearerAuth' => []]],
        tags: ['hook','admin'],
        parameters: [new OA\PathParameter( name: 'thing_hook', description: "uuid of the hook", in: 'path', required: true,  schema: new OA\Schema( type: 'string',format: 'uuid') )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_ACCEPTED, description: 'The shown hook',content: new JsonContent(ref: HookResponse::class)),
            new OA\Response( response: CodeOf::HTTP_BAD_REQUEST, description: 'When not logged in',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_BAD_REQUEST,"message"=>"Unauthenticated."])),
            new OA\Response( response: CodeOf::HTTP_FORBIDDEN, description: 'When not admin',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_FORBIDDEN,"message"=>"Not an admin."]))
        ]
    )]
    public function admin_hook_destroy(ThingHook $hook) {
        $hook->delete();
        return response()->json(new HookResponse(hook: $hook), CodeOf::HTTP_ACCEPTED);
    }

    #[OA\Get(
        path: '/api/hbc-things/v1/hooks/admin/{thing_hook}/show',
        operationId: 'hbc-things.hooks.admin.show',
        description: "",
        summary: 'Shows information about a hook',
        security: [['bearerAuth' => []]],
        tags: ['hook','admin'],
        parameters: [new OA\PathParameter( name: 'thing_hook', description: "uuid of the hook", in: 'path', required: true,  schema: new OA\Schema( type: 'string',format: 'uuid') )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_OK, description: 'The shown hook',content: new JsonContent(ref: HookResponse::class)),

            new OA\Response( response: CodeOf::HTTP_BAD_REQUEST, description: 'When not logged in',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_BAD_REQUEST,"message"=>"Unauthenticated."])),

            new OA\Response( response: CodeOf::HTTP_FORBIDDEN, description: 'When not admin',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_FORBIDDEN,"message"=>"Not an admin."]))
        ]
    )]
    public function admin_hook_show(ThingHook $hook) {
        return response()->json(new HookResponse(hook: $hook,b_include_callbacks: true), CodeOf::HTTP_OK);
    }


    #[OA\Post(
        path: '/api/hbc-things/v1/hooks/create',
        operationId: 'hbc-things.hooks.create',
        description: "Make a hook with or without defined callback responses",
        summary: 'Create a new hook',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody( content: [
            new OA\MediaType(mediaType: "application/json",schema: new  OA\Schema(ref: HookParams::class)),
            new OA\MediaType(mediaType: "multipart/form-data",schema: new  OA\Schema(ref: HookParams::class))
        ] ),
        tags: ['hook'],
        responses: [
            new OA\Response( response: CodeOf::HTTP_CREATED, description: 'The created hook',content: new JsonContent(ref: HookResponse::class)),
            new OA\Response( response: CodeOf::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation fails', content: new JsonContent(ref: ErrorResponse::class)),
            new OA\Response( response: CodeOf::HTTP_BAD_REQUEST, description: 'When not logged in',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_BAD_REQUEST,"message"=>"Unauthenticated."]))
        ]
    )]
    public function thing_hook_create(IThingOwner $owner,HookRequest $request) {

        $data = HookParams::fromRequest(request: $request);
        $data->setHookOwner($owner);
        $hook = ThingHook::createHook($data);
        return response()->json(new HookResponse(hook: $hook), CodeOf::HTTP_CREATED);
    }


    #[OA\Get(
        path: '/api/hbc-things/v1/hooks/{thing_hook}/show',
        operationId: 'hbc-things.hooks.show',
        description: "",
        summary: 'Shows information about a hook',
        security: [['bearerAuth' => []]],
        tags: ['hook'],
        parameters: [new OA\PathParameter( name: 'thing_hook', description: "uuid of the hook", in: 'path', required: true,  schema: new OA\Schema( type: 'string',format: 'uuid') )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_OK, description: 'The shown hook',content: new JsonContent(ref: HookResponse::class)),

            new OA\Response( response: CodeOf::HTTP_BAD_REQUEST, description: 'When not logged in',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_BAD_REQUEST,"message"=>"Unauthenticated."]))
        ]
    )]
    public function thing_hook_show(ThingHook $hook) {
        return response()->json(new HookResponse(hook: $hook,b_include_callbacks: true), CodeOf::HTTP_OK);
    }

    #[OA\Patch(
        path: '/api/hbc-things/v1/hooks/{thing_hook}/edit',
        operationId: 'hbc-things.hooks.edit',
        description: "Affects future hooks",
        summary: 'Edits a hook',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody( content: [
            new OA\MediaType(mediaType: "application/json",schema: new  OA\Schema(ref: HookParams::class)),
            new OA\MediaType(mediaType: "multipart/form-data",schema: new  OA\Schema(ref: HookParams::class)),
        ] ),
        tags: ['hook'],
        parameters: [new OA\PathParameter( name: 'thing_hook', description: "uuid of the hook", in: 'path', required: true,  schema: new OA\Schema( type: 'string',format: 'uuid') )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_OK, description: 'The edited hook',content: new JsonContent(ref: HookResponse::class)),
            new OA\Response( response: CodeOf::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation fails', content: new JsonContent(ref: ErrorResponse::class)),
            new OA\Response( response: CodeOf::HTTP_BAD_REQUEST, description: 'When not logged in',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_BAD_REQUEST,"message"=>"Unauthenticated."]))
        ]
    )]
    public function thing_hook_edit(ThingHook $hook,HookRequest $request) {
        $request->offsetUnset('is_manual'); //cannot change after creation
        $data = HookParams::fromRequest(request: $request);
        $hook->updateHook($data);
        $hook->save();
        return response()->json(new HookResponse(hook: $hook), CodeOf::HTTP_OK);
    }


    #[OA\Delete(
        path: '/api/hbc-things/v1/hooks/{thing_hook}/destroy',
        operationId: 'hbc-things.hooks.destroy',
        description: "",
        summary: 'Deletes a hook',
        security: [['bearerAuth' => []]],
        tags: ['hook'],
        parameters: [new OA\PathParameter( name: 'thing_hook', description: "uuid of the hook", in: 'path', required: true,  schema: new OA\Schema( type: 'string',format: 'uuid') )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_ACCEPTED, description: 'The shown hook',content: new JsonContent(ref: HookResponse::class)),
            new OA\Response( response: CodeOf::HTTP_BAD_REQUEST, description: 'When not logged in',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_BAD_REQUEST,"message"=>"Unauthenticated."])),
            new OA\Response( response: CodeOf::HTTP_FORBIDDEN, description: 'When not admin',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_FORBIDDEN,"message"=>"Not an admin."]))
        ]
    )]
    public function thing_hook_destroy(ThingHook $hook) {
        $hook->delete();
        return response()->json(new HookResponse(hook: $hook), CodeOf::HTTP_ACCEPTED);
    }



    #[OA\Get(
        path: '/api/hbc-things/v1/things/list',
        operationId: 'hbc-things.things.list',
        description: "Shows tree status, times, progress",
        summary: 'Lists top things that are owned by user',
        security: [['bearerAuth' => []]],
        tags: ['thing'],
        parameters: [new OA\QueryParameter( name: 'Search things', description: "Optionally search", in: 'query',
            allowEmptyValue: true, schema: new OA\Schema( ref: ThingSearchParams::class) )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_OK, description: 'The things',content: new JsonContent(ref: ThingCollectionResponse::class)),
            new OA\Response( response: CodeOf::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation fails', content: new JsonContent(ref: ErrorResponse::class)),
            new OA\Response( response: CodeOf::HTTP_BAD_REQUEST, description: 'When not logged in',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_BAD_REQUEST,"message"=>"Unauthenticated."])),
        ]
    )]
    public function thing_list(IThingOwner $owner, ThingSearchRequest $request) {
        $search = ThingSearchParams::fromRequest(request:$request);
        if ($search->getIsRoot() === null) { $search->setIsRoot(true);}
        $things = Thing::buildThing( owner_group: $owner,group_hint: TypeOfOwnerGroup::THING_LIST, params: $search)
            ->orderBy('id','desc')->cursorPaginate();
        return response()->json(new ThingCollectionResponse(given_things: $things), CodeOf::HTTP_OK);
    }


    #[OA\Get(
        path: '/api/hbc-things/v1/things/admin/list',
        operationId: 'hbc-things.things.admin.list',
        description: "Shows tree status, times, progress",
        summary: 'Lists top things that are owned by user',
        security: [['bearerAuth' => []]],
        tags: ['thing','admin'],
        parameters: [new OA\QueryParameter( name: 'Search things', description: "Optionally search", in: 'query',
            allowEmptyValue: true, schema: new OA\Schema( ref: ThingSearchParams::class) )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_OK, description: 'The things',content: new JsonContent(ref: ThingCollectionResponse::class)),
            new OA\Response( response: CodeOf::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation fails', content: new JsonContent(ref: ErrorResponse::class)),
            new OA\Response( response: CodeOf::HTTP_BAD_REQUEST, description: 'When not logged in',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_BAD_REQUEST,"message"=>"Unauthenticated."])),
            new OA\Response( response: CodeOf::HTTP_FORBIDDEN, description: 'When not admin',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_FORBIDDEN,"message"=>"Not an admin."]))
        ]
    )]
    public function thing_admin_list(ThingSearchRequest $request) {
        $search = ThingSearchParams::fromRequest(request:$request);
        if ($search->getIsRoot() === null) { $search->setIsRoot(true);}
        $things = Thing::buildThing( is_root: true,params: $search)
            ->orderBy('id','desc')->cursorPaginate();
        return response()->json(new ThingCollectionResponse(given_things: $things), CodeOf::HTTP_OK);
    }

    #[OA\Get(
        path: '/api/hbc-things/v1/things/{thing}/show',
        operationId: 'hbc-things.things.show',
        description: "Lesser detail in decendants",
        summary: 'Shows a thing and its descendants',
        security: [['bearerAuth' => []]],
        tags: ['thing'],
        parameters: [new OA\PathParameter( name: 'thing', description: "uuid of the thing", in: 'path', required: true,  schema: new OA\Schema( type: 'string',format: 'uuid') )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_OK, description: 'The thing',content: new JsonContent(ref: ThingResponse::class)),

            new OA\Response( response: CodeOf::HTTP_BAD_REQUEST, description: 'When not logged in',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_BAD_REQUEST,"message"=>"Unauthenticated."])),
        ]
    )]
    public function thing_show(Thing $thing) { //  (a tree)
        return response()->json(new ThingResponse(thing: $thing, b_include_hooks: true, b_include_children: true), CodeOf::HTTP_OK);
    }



    #[OA\Get(
        path: '/api/hbc-things/v1/things/admin/{thing}/show',
        operationId: 'hbc-things.things.admin.show',
        description: "Lesser detail in decendants",
        summary: 'Shows a thing and its descendants',
        security: [['bearerAuth' => []]],
        tags: ['thing','admin'],
        parameters: [new OA\PathParameter( name: 'thing', description: "uuid of the thing", in: 'path', required: true,  schema: new OA\Schema( type: 'string',format: 'uuid') )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_OK, description: 'The thing',content: new JsonContent(ref: ThingResponse::class)),

            new OA\Response( response: CodeOf::HTTP_BAD_REQUEST, description: 'When not logged in',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_BAD_REQUEST,"message"=>"Unauthenticated."])),
            new OA\Response( response: CodeOf::HTTP_FORBIDDEN, description: 'When not admin',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_FORBIDDEN,"message"=>"Not an admin."]))
        ]
    )]
    public function admin_thing_show(Thing $thing) {
        return response()->json(new ThingResponse(thing: $thing, b_include_hooks: true, b_include_children: true), CodeOf::HTTP_OK);
    }







    #[OA\Put(
        path: '/api/hbc-things/v1/things/{thing}/shortcut',
        operationId: 'hbc-things.things.shortcut',
        description: "If children not run they are shortcut too",
        summary: 'Shortcuts a thing',
        security: [['bearerAuth' => []]],
        tags: ['thing'],
        parameters: [new OA\PathParameter( name: 'thing', description: "uuid of the thing", in: 'path', required: true,  schema: new OA\Schema( type: 'string',format: 'uuid') )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_ACCEPTED, description: 'The thing',content: new JsonContent(ref: ThingResponse::class)),

            new OA\Response( response: CodeOf::HTTP_BAD_REQUEST, description: 'When not logged in',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_BAD_REQUEST,"message"=>"Unauthenticated."]))
        ]
    )]
    public function thing_shortcut(Thing $thing) {  //(if child will return false to parent when it runs, if root then its just gone)
        $thing->markIncompleteDescendantsAs(TypeOfThingStatus::THING_SHORT_CIRCUITED);
        /** @var Thing $refreshed */
        $refreshed = Thing::buildThing(me_id: $thing->id)->first();
        return response()->json(new ThingResponse(thing: $refreshed, b_include_hooks: true, b_include_children: true), CodeOf::HTTP_ACCEPTED);
    }

    /**
     * @throws \Exception
     */
    #[OA\Put(
        path: '/api/hbc-things/v1/things/{thing}/resume',
        operationId: 'hbc-things.things.resume',
        description: "If a thing is waiting, it is dispatched again",
        summary: 'Wakes up a thing if its waiting',
        security: [['bearerAuth' => []]],
        tags: ['thing'],
        parameters: [new OA\PathParameter( name: 'thing', description: "uuid of the thing", in: 'path', required: true,  schema: new OA\Schema( type: 'string',format: 'uuid') )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_ACCEPTED, description: 'The thing was resumed, here is the thing info',content: new JsonContent(ref: ThingResponse::class)),

            new OA\Response( response: CodeOf::HTTP_NOT_FOUND, description: 'The thing was not waiting',content: new JsonContent(ref: ErrorResponse::class)),

            new OA\Response( response: CodeOf::HTTP_BAD_REQUEST, description: 'When not logged in',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_BAD_REQUEST,"message"=>"Unauthenticated."]))
        ]
    )]
    public function thing_resume(Thing $thing) {
        if ($thing->thing_status !== TypeOfThingStatus::THING_WAITING) { abort(CodeOf::HTTP_NOT_FOUND);}
        $thing->continueThing();
        /** @var Thing $refreshed */
        $refreshed = Thing::buildThing(me_id: $thing->id)->first();
        return response()->json(new ThingResponse(thing: $refreshed, b_include_hooks: true, b_include_children: true), CodeOf::HTTP_ACCEPTED);
    }


    /**
     * @throws \Exception
     */
    #[OA\Post(
        path: '/api/hbc-things/v1/callbacks/manual/{thing_callback}/answer',
        operationId: 'hbc-things.callbacks.manual_answer',
        description: "Manual callbacks can be filled in without auth, if they are waiting",
        summary: 'Fill in a manual callback',
        requestBody: new OA\RequestBody( content: [
            new OA\MediaType(mediaType: "application/json",schema: new  OA\Schema(ref: ManualParams::class)),
            new OA\MediaType(mediaType: "multipart/form-data",schema: new  OA\Schema(ref: ManualParams::class))
        ] ),
        tags: ['callback','manual'],
        parameters: [
            new OA\PathParameter( name: 'thing_callback', description: "uuid of the callback", in: 'path', required: true,
                schema: new OA\Schema( type: 'string',format: 'uuid') )
        ],
        responses: [
            new OA\Response( response: CodeOf::HTTP_ACCEPTED, description: 'The callback',content: new JsonContent(ref: CallbackResponse::class)),

            new OA\Response( response: CodeOf::HTTP_BAD_REQUEST, description: 'When not logged in',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_BAD_REQUEST,"message"=>"Unauthenticated."]))
        ]
    )]
    public function manual_answer(ThingCallback $callback,ManualFillRequest $request) {
        if (!$callback->owning_hook->is_manual) { abort(CodeOf::HTTP_BAD_REQUEST);}

        if (!$callback->manual_alert_callback_id) {
            /** @var ThingCallback|null $working */
            $working = ThingCallback::buildCallback(alerted_by_callback_id: $callback->id)->first();
            if (!$working) {
                throw new HbcThingException("Could not find paired manual callback by alert id ". $callback->ref_uuid);
            }
        } else {
            $working = $callback;
        }

        if ($working->thing_callback_status !== TypeOfCallbackStatus::WAITING) { abort(CodeOf::HTTP_NOT_FOUND);}

        $params = ManualParams::fromRequest(request: $request);
        $working->setManualAnswer($params);
        $working->refresh();
        return response()->json(new CallbackResponse(callback: $callback, b_include_hook:  true,b_include_thing: true), CodeOf::HTTP_ACCEPTED);
    }

    #[OA\Get(
        path: '/api/hbc-things/v1/callbacks/manual/{thing_callback}/question',
        operationId: 'hbc-things.callbacks.manual_question',
        description: "Manual callbacks can be shown without auth, if they are waiting",
        summary: 'Show a waiting manual callback',
        tags: ['callback','manual'],
        parameters: [
            new OA\PathParameter( name: 'thing_callback', description: "uuid of the callback", in: 'path', required: true,  schema: new OA\Schema( type: 'string',format: 'uuid') )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_ACCEPTED, description: 'The callback',content: new JsonContent(ref: CallbackResponse::class)),

            new OA\Response( response: CodeOf::HTTP_BAD_REQUEST, description: 'When not logged in',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_BAD_REQUEST,"message"=>"Unauthenticated."]))
        ]
    )]
    public function manual_question(ThingCallback $callback) {
        if (!$callback->owning_hook->is_manual) { abort(CodeOf::HTTP_BAD_REQUEST);}

        if (!$callback->manual_alert_callback_id) {
            /** @var ThingCallback|null $working */
            $working = ThingCallback::buildCallback(alerted_by_callback_id: $callback->id)->first();
            if (!$working) {
                throw new HbcThingException("Could not find manual callback by alert id ". $callback->ref_uuid);
            }
        } else {
            $working = $callback;
        }

        if ($working->thing_callback_status !== TypeOfCallbackStatus::WAITING) { abort(CodeOf::HTTP_NOT_FOUND);}
        return response()->json(new CallbackResponse(callback: $working, b_include_hook:  true,b_include_thing: true), CodeOf::HTTP_ACCEPTED);
    }

    #[OA\Get(
        path: '/api/hbc-things/v1/callbacks/{thing_callback}/show',
        operationId: 'hbc-things.callbacks.show',
        description: "",
        summary: 'Show a callback',
        security: [['bearerAuth' => []]],
        tags: ['callback'],
        parameters: [new OA\PathParameter( name: 'thing_callback', description: "uuid of the callback", in: 'path', required: true,  schema: new OA\Schema( type: 'string',format: 'uuid') )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_OK, description: 'The callback',content: new JsonContent(ref: CallbackResponse::class)),

            new OA\Response( response: CodeOf::HTTP_BAD_REQUEST, description: 'When not logged in',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_BAD_REQUEST,"message"=>"Unauthenticated."]))
        ]
    )]
    public function callback_show(ThingCallback $callback) {
        return response()->json(new CallbackResponse(callback: $callback, b_include_hook:  true,b_include_thing: true), CodeOf::HTTP_OK);
    }

    #[OA\Get(
        path: '/api/hbc-things/v1/callbacks/list',
        operationId: 'hbc-things.callbacks.list',
        description: "",
        summary: 'Show a list of callbacks',
        security: [['bearerAuth' => []]],
        tags: ['callback'],
        parameters: [new OA\QueryParameter( name: 'Search params', description: "Optionally search", in: 'query',
            allowEmptyValue: true, schema: new OA\Schema( ref: CallbackSearchParams::class) )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_OK, description: 'The callback list',content: new JsonContent(ref: CallbackCollectionResponse::class)),
            new OA\Response( response: CodeOf::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation fails', content: new JsonContent(ref: ErrorResponse::class)),
            new OA\Response( response: CodeOf::HTTP_BAD_REQUEST, description: 'When not logged in',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_BAD_REQUEST,"message"=>"Unauthenticated."]))
        ]
    )]
    public function list_callbacks( IThingOwner $owner,CallbackSearchRequest $request) {
        $search = CallbackSearchParams::fromRequest(request:$request);
        $callbacks = ThingCallback::buildCallback(
             owner_group: $owner,group_hint: TypeOfOwnerGroup::CALLBACK_LIST,params: $search
        )->orderBy('id','desc')->cursorPaginate();
        return response()->json(new CallbackCollectionResponse(given_callbacks: $callbacks,), CodeOf::HTTP_OK);
    }


    #[OA\Get(
        path: '/api/hbc-things/v1/callbacks/admin/list',
        operationId: 'hbc-things.callbacks.admin.list',
        description: "",
        summary: 'Show a list of callbacks',
        security: [['bearerAuth' => []]],
        tags: ['callback','admin'],
        parameters: [new OA\QueryParameter( name: 'Search params', description: "Optionally search", in: 'query',
            allowEmptyValue: true, schema: new OA\Schema( ref: CallbackSearchParams::class) )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_OK, description: 'The callback list',content: new JsonContent(ref: CallbackCollectionResponse::class)),
            new OA\Response( response: CodeOf::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation fails', content: new JsonContent(ref: ErrorResponse::class)),
            new OA\Response( response: CodeOf::HTTP_BAD_REQUEST, description: 'When not logged in',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_BAD_REQUEST,"message"=>"Unauthenticated."]))
        ]
    )]
    public function admin_list_callbacks( CallbackSearchRequest $request) {
        $search = CallbackSearchParams::fromRequest(request:$request);
        $callbacks = ThingCallback::buildCallback(params: $search)
            ->orderBy('id','desc')->cursorPaginate();
        return response()->json(new CallbackCollectionResponse(given_callbacks: $callbacks,), CodeOf::HTTP_OK);
    }


    #[OA\Get(
        path: '/api/hbc-things/v1/callbacks/admin/{thing_callback}/show',
        operationId: 'hbc-things.callbacks.admin.show',
        description: "",
        summary: 'Show a callback',
        security: [['bearerAuth' => []]],
        tags: ['callback','admin'],
        parameters: [new OA\PathParameter( name: 'thing_callback', description: "uuid of the callback", in: 'path', required: true,  schema: new OA\Schema( type: 'string',format: 'uuid') )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_OK, description: 'The callback',content: new JsonContent(ref: CallbackResponse::class)),

            new OA\Response( response: CodeOf::HTTP_BAD_REQUEST, description: 'When not logged in',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_BAD_REQUEST,"message"=>"Unauthenticated."])),

            new OA\Response( response: CodeOf::HTTP_FORBIDDEN, description: 'When not admin',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_FORBIDDEN,"message"=>"Not an admin."]))
        ]
    )]
    public function admin_callback_show(ThingCallback $callback) {
        return response()->json(new CallbackResponse(callback: $callback, b_include_hook:  true,b_include_thing: true), CodeOf::HTTP_OK);
    }


}
