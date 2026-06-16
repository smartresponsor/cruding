<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('cruding_api_tokenized_catch_all', '/api/{crudPath}')
        ->controller('App\Cruding\Controller\Api\Crud\CrudApiTokenizedController')
        ->methods(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])
        ->defaults([
            '_crud_surface' => 'public',
        ])
        ->requirements([
            'crudPath' => '%cruding.resource_requirement%(?:/.*)?',
        ]);

    $routes->add('cruding_tokenized_catch_all', '/{crudPath}')
        ->controller('App\Cruding\Controller\Crud\CrudTokenizedController')
        ->methods(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])
        ->defaults([
            '_crud_surface' => 'public',
        ])
        ->requirements([
            'crudPath' => '%cruding.resource_requirement%(?:/.*)?',
        ]);

    $subjectRequirement = '(?!show$|index$|new$|create$|edit$|update$|delete$|bulk$|import$|export$|archive$|restore$|duplicate$|card$|table$|gallery$|compact$|full$|detail$|list$)[A-Za-z0-9][A-Za-z0-9_-]*';

    $routes->add('cruding_surface_token_item_action', '/{resource}/{subject}/{surface}/{token}/{item}/{action}')
        ->controller('App\Cruding\Controller\Surface\CrudSurfaceController')
        ->methods(['GET'])
        ->requirements([
            'resource' => '%cruding.resource_requirement%',
            'subject' => $subjectRequirement,
            'surface' => '[a-z0-9][a-z0-9_-]*',
            'token' => '%cruding.surface_token_requirement%',
            'item' => '[A-Za-z0-9][A-Za-z0-9_-]*',
            'action' => '[a-z][a-z0-9_-]*',
        ]);

    $routes->add('cruding_surface_token_item', '/{resource}/{subject}/{surface}/{token}/{item}')
        ->controller('App\Cruding\Controller\Surface\CrudSurfaceController')
        ->methods(['GET'])
        ->requirements([
            'resource' => '%cruding.resource_requirement%',
            'subject' => $subjectRequirement,
            'surface' => '[a-z0-9][a-z0-9_-]*',
            'token' => '%cruding.surface_token_requirement%',
            'item' => '[A-Za-z0-9][A-Za-z0-9_-]*',
        ]);

    $routes->add('cruding_surface_item_action', '/{resource}/{subject}/{surface}/{item}/{action}')
        ->controller('App\Cruding\Controller\Surface\CrudSurfaceController')
        ->methods(['GET'])
        ->requirements([
            'resource' => '%cruding.resource_requirement%',
            'subject' => $subjectRequirement,
            'surface' => '[a-z0-9][a-z0-9_-]*',
            'item' => '[A-Za-z0-9][A-Za-z0-9_-]*',
            'action' => '[a-z][a-z0-9_-]*',
        ]);

    $routes->add('cruding_surface_action', '/{resource}/{subject}/{surface}/{action}')
        ->controller('App\Cruding\Controller\Surface\CrudSurfaceController')
        ->methods(['GET'])
        ->requirements([
            'resource' => '%cruding.resource_requirement%',
            'subject' => $subjectRequirement,
            'surface' => '[a-z0-9][a-z0-9_-]*',
            'action' => '[a-z][a-z0-9_-]*',
        ]);

    $routes->add('cruding_surface_index', '/{resource}/{subject}/{surface}')
        ->controller('App\Cruding\Controller\Surface\CrudSurfaceController')
        ->methods(['GET'])
        ->requirements([
            'resource' => '%cruding.resource_requirement%',
            'subject' => $subjectRequirement,
            'surface' => '[a-z0-9][a-z0-9_-]*',
        ]);
};
