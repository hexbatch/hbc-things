<?php

namespace Hexbatch\Things\Controllers;

use Symfony\Component\HttpFoundation\Response as CodeOf;
use OpenApi\Attributes as OA;

class ThingController  {


    #[OA\Get(
        path: '/api/v1/{namespace}/things/hooks/list',
        operationId: 'core.things.hooks.list',
        description: "",
        summary: 'List all the hooks',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_hook_list() {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

    #[OA\Post(
        path: '/api/v1/{namespace}/things/hooks/create',
        operationId: 'core.things.hooks.create',
        description: "",
        summary: 'Create a new hook',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_hook_create() {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }


    #[OA\Get(
        path: '/api/v1/{namespace}/things/hooks/show',
        operationId: 'core.things.hooks.show',
        description: "",
        summary: 'Shows information about a hook',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_hook_show() {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

    #[OA\Patch(
        path: '/api/v1/{namespace}/things/hooks/edit',
        operationId: 'core.things.hooks.edit',
        description: "",
        summary: 'Edits a hook',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_hook_edit() {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }


    #[OA\Delete(
        path: '/api/v1/{namespace}/things/hooks/destroy',
        operationId: 'core.things.hooks.destroy',
        description: "",
        summary: 'Edits a hook',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_hook_destroy() {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

















    #[OA\Get(
        path: '/api/v1/{namespace}/things/list',
        operationId: 'core.things.list',
        description: "",
        summary: 'Lists things, searchable',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_list() { //(top roots) also search
        // list/search/view thing nodes and trees
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

    #[OA\Get(
        path: '/api/v1/{namespace}/things/show',
        operationId: 'core.things.show',
        description: "",
        summary: 'Shows a thing and its descendants',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_show() { //  (a tree)
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }


    #[OA\Get(
        path: '/api/v1/{namespace}/things/inspect',
        operationId: 'core.things.inspect',
        description: "",
        summary: 'Inspects a single thing',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_inspect() { //  (a single thing)
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }




    #[OA\Put(
        path: '/api/v1/{namespace}/things/trim',
        operationId: 'core.things.trim',
        description: "Parents of trimmed things will return false",
        summary: 'Removes a thing and its descendants',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_shortcut() {  //(if child will return false to parent when it runs, if root then its just gone)
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }


    #[OA\Post(
        path: '/api/v1/{namespace}/things/rates/apply',
        operationId: 'core.things.rates.apply',
        description: "",
        summary: 'Applies a rate(s) to a thing and its descendants',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_setting_create() {

        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

    #[OA\Delete(
        path: '/api/v1/{namespace}/things/rates/remove',
        operationId: 'core.things.rates.remove',
        description: "",
        summary: 'Removes a rate(s) to a thing and its descendants',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_setting_remove() {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }


    #[OA\Get(
        path: '/api/v1/{namespace}/things/rates/list',
        operationId: 'core.things.rates.list',
        description: "",
        summary: 'Lists the rates that apply to a thing and its descendants',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_setting_list() { //also search
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

    #[OA\Get(
        path: '/api/v1/{namespace}/things/rates/show',
        operationId: 'core.things.rates.show',
        description: "",
        summary: 'Shows information about a setting/rate',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_setting_show() { //also search
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

    #[OA\Get(
        path: '/api/v1/{namespace}/things/rates/edit',
        operationId: 'core.things.rates.edit',
        description: "",
        summary: 'Edit a setting/rate',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_callback_show() { //also search
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

    #[OA\Post(
        path: '/api/v1/{namespace}/things/rates/edit',
        operationId: 'core.things.rates.edit',
        description: "",
        summary: 'Edit a setting/rate',
        parameters: [new OA\PathParameter(  ref: '#/components/parameters/namespace' )],
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_callback_create() { //also search
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }


}
