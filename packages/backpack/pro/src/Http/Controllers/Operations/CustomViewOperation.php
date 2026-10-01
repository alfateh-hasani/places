<?php

namespace Backpack\Pro\Http\Controllers\Operations;

use Exception;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

trait CustomViewOperation
{
    /**
     * Define which routes are needed for this operation.
     *
     * @param  string  $segment  Name of the current entity (singular). Used as first URL segment.
     * @param  string  $route  Prefix of the route name.
     * @param  string  $controller  Name of the current CrudController.
     */
    protected function setupCustomViewRoutes(string $segment, string $route, string $controller): void
    {
        $customViews = $this->getCustomViews();

        // setup routes
        foreach ($customViews as $method => $data) {
            Route::get($route.'/view/'.Str::kebab($data->title), [
                'as'        => $route.'.view.'.Str::camel($data->title),
                'uses'      => $controller.'@index',
                'operation' => 'list',
            ]);
        }
    }

    /**
     * Get the custom views from the CrudController.
     *
     * @return array
     */
    protected function getCustomViews(): array
    {
        return collect(get_class_methods($this))
            ->mapWithKeys(function ($item) {
                preg_match('/^setup(.+)View$/', $item, $match);

                return [$match[0] ?? false => (object) [
                    'method' => $match[0] ?? false,
                    'title'  => Str::of($match[1] ?? false)->kebab()->replace('-', ' ')->title()->toString(),
                    'route'  => Str::kebab($match[1] ?? false),
                ]];
            })
            ->filter(fn ($value, $key) => $key)
            ->toArray();
    }

    /**
     * Add the default settings, buttons, etc that this operation needs.
     *
     * @return void
     */
    protected function setupCustomViewDefaults(): void
    {
        $this->crud->allowAccess('customView');

        $this->crud->operation('list', function () {
            $this->crud->addButton('top', 'custom_view', 'view', 'crud::buttons.custom_view');
        });
    }

    /**
     * Run the custom view setup methods.
     *
     * @param array|null $viewTitles
     * @return void
     */
    protected function runCustomViews(?array $viewTitles = null)
    {
        $this->crud->hasAccessOrFail('customView');

        $customViews = $this->getCustomViews();

        // Update titles
        foreach ($viewTitles ?? [] as $method => $title) {
            if (! ($customViews[$method] ?? false)) {
                throw new Exception(sprintf('The method "%1$s" is missing on "%2$s".', $method, get_class($this)));
            }

            $customViews[$method]->title = $title;
        }

        // Order the views
        if ($viewTitles) {
            $customViews = array_merge(array_flip(array_keys($viewTitles)), $customViews);
        }

        $this->crud->setOperationSetting('customViews', $customViews);

        // Get the current view
        $currentView = collect($customViews)
            ->filter(function ($view) {
                return $this->requestInitiatedByCustomView($view) || $this->isCustomView($view);
            })->first();

        if ($currentView?->method === null || ! method_exists($this, $currentView?->method)) {
            return;
        }

        // run the setup method if we are in a custom view or a custom view is making a json request
        $this->{$currentView?->method}();

        // if we currently are in a custom view
        if ($this->isCustomView($currentView)) {
            // remove the filters
            $this->crud->set('list.filters', collect());
            // set the heading
            $this->crud->set('index.heading', $currentView->title);

            return;
        }
    }

    private function isCustomView($view): bool
    {
        return $view->route === basename(request()->getRequestUri());
    }

    private function requestInitiatedByCustomView($view): bool
    {
        return $view->route === basename(request()->headers->get('referer')) && request()->wantsJson();
    }
}
