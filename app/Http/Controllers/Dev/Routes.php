<?php

namespace App\Http\Controllers\Dev;

use Closure;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;

class Routes extends Controller
{
    /**
     * The table headers for the command.
     *
     * @var string[]
     */
    protected $headers = ['Domain', 'Method', 'URI', 'Name', 'Action', 'Middleware'];

    /**
     * Вывод всех маршрутов
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRoutes(Request $request)
    {
        $routes = collect(Route::getRoutes())->map(function ($route) {
            return $this->getRouteInformation($route);
        })->filter()->all();

        return response()->json([
            'routes' => $this->sortRoutes("uri", $routes),
            'headers' => $this->headers,
        ]);
    }

    /**
     * Get the route information for a given route.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return array
     */
    protected function getRouteInformation($route)
    {
        return [
            'domain' => $route->domain(),
            'method' => implode('|', $route->methods()),
            'uri' => $route->uri(),
            'name' => $route->getName(),
            'action' => ltrim($route->getActionName(), '\\'),
            'middleware' => $this->getMiddlewareRoute($route),
        ];
    }

    /**
     * Get the middleware for the route.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return string
     */
    protected function getMiddlewareRoute($route)
    {
        return collect(Route::gatherRouteMiddleware($route))->map(function ($middleware) {
            return $middleware instanceof Closure ? 'Closure' : $middleware;
        })->implode("\n");
    }

    /**
     * Sort the routes by a given element.
     *
     * @param  string  $sort
     * @param  array  $routes
     * @return array
     */
    protected function sortRoutes($sort, array $routes)
    {
        $sortable = Arr::sort($routes, function ($route) use ($sort) {
            return $route[$sort];
        });

        foreach ($sortable as $row) {
            $data[] = $row;
        }

        return $data ?? [];
    }
}
