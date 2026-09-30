<?php

namespace Backpack\Pro\Http\Controllers\Operations;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

trait FetchOperation
{
    /**
     * Define which routes are needed for this operation.
     *
     * @param  string  $segment  Name of the current entity (singular). Used as first URL segment.
     * @param  string  $routeName  Prefix of the route name.
     * @param  string  $controller  Name of the current CrudController.
     */
    protected function setupFetchOperationRoutes($segment, $routeName, $controller)
    {
        // get all method names on the current model that start with "fetch" (ex: fetchCategory)
        // if a method that looks like that is present, it means we need to add the routes that fetch that entity
        preg_match_all('/(?<=^|;)fetch([^;]+?)(;|$)/', implode(';', get_class_methods($this)), $matches);

        if (count($matches[1])) {
            foreach ($matches[1] as $methodName) {
                Route::post($segment.'/fetch/'.Str::kebab($methodName), [
                    'as'        => $segment.'.fetch'.Str::studly($methodName),
                    'uses'      => $controller.'@fetch'.$methodName,
                    'operation' => 'FetchOperation',
                ]);
            }
        }
    }

    protected function setupFetchOperationDefaults()
    {
        $this->crud->allowAccess('fetch');
        $this->crud->setOperationSetting('searchOperator', config('backpack.operations.fetch.searchOperator', 'LIKE'));
    }

    /**
     * Gets items from database and returns to selects.
     *
     * @param  string|array  $arg
     * @return \Illuminate\Http\JsonResponse|Illuminate\Database\Eloquent\Collection|Illuminate\Pagination\LengthAwarePaginator
     */
    private function fetch($arg)
    {
        $this->crud->hasAccessOrFail('fetch');

        // get the actual words that were used to search for an item (the search term / search string)
        $search_string = request()->input('q') ?? false;

        // if the Class was passed as the sole argument, use that as the configured Model
        // otherwise assume the arguments are actually the configuration array
        $config = [];

        if (! is_array($arg)) {
            if (! class_exists($arg)) {
                return response()->json(['error' => 'Class: '.$arg.' does not exists'], 500);
            }
            $config['model'] = $arg;
        } else {
            $config = $arg;
        }

        $model_instance = new $config['model']();
        // set configuration defaults
        $config['paginate'] = isset($config['paginate']) ? $config['paginate'] : 10;
        $config['searchable_attributes'] = $config['searchable_attributes'] ?? $model_instance->identifiableAttribute();
        // if a closure that has been passed as "query", use the closure - otherwise use the model
        $config['query'] = isset($config['query']) && is_callable($config['query']) ? $config['query']($model_instance) : $model_instance;

        // FetchOperation sends an empty query to retrieve the default entry for select when field is not nullable.
        // Also sends an empty query in case we want to load all entities to emulate non-ajax fields
        // when using InlineCreate.

        if ($search_string === false) {
            return $this->processFetchResults($config);
        }

        $textColumnTypes = ['string', 'json_string', 'text', 'longText', 'json_array', 'json', 'varchar', 'char'];

        $searchOperator = $config['searchOperator'] ?? $this->crud->getOperationSetting('searchOperator') ?? 'LIKE';

        // Check if model is translatable
        $isTranslatable = method_exists($model_instance, 'translationEnabledForModel') && 
                         $model_instance->translationEnabledForModel();

        // If config['query'] is a Model instance, convert it to QueryBuilder
        if (is_a($config['query'], 'Illuminate\Database\Eloquent\Model')) {
            $config['query'] = $config['query']->newQuery();
        }

        // Apply search conditions to the query
        if (! empty($config['query']->getQuery()->wheres)) {
            // If there are existing WHERE clauses, wrap our search in an additional WHERE group
            $config['query']->where(function ($subQuery) use ($model_instance, $config, $search_string, $textColumnTypes, $searchOperator, $isTranslatable) {
                $this->applySearchConditions($subQuery, $model_instance, $config['searchable_attributes'], $search_string, $textColumnTypes, $searchOperator, $isTranslatable);
            });
        } else {
            // Apply search conditions directly to the main query
            $this->applySearchConditions($config['query'], $model_instance, $config['searchable_attributes'], $search_string, $textColumnTypes, $searchOperator, $isTranslatable);
        }

        // processes the results by appending the attributes that were requested by the developer
        return $this->processFetchResults($config);
    }

    /**
     * Apply search conditions for both translatable and non-translatable attributes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $model_instance
     * @param  array  $searchable_attributes
     * @param  string  $search_string
     * @param  array  $textColumnTypes
     * @param  string  $searchOperator
     * @param  bool  $isTranslatable
     * @return void
     */
    private function applySearchConditions($query, $model_instance, $searchable_attributes, $search_string, $textColumnTypes, $searchOperator, $isTranslatable)
    {
        foreach ((array) $searchable_attributes as $k => $searchColumn) {
            $operation = ($k == 0) ? 'where' : 'orWhere';
            
            // Check if this specific attribute is translatable
            $isAttributeTranslatable = $isTranslatable && 
                                      method_exists($model_instance, 'isTranslatableAttribute') && 
                                      $model_instance->isTranslatableAttribute($searchColumn);

            if ($isAttributeTranslatable) {
                $this->applyTranslatableSearch($query, $model_instance, $searchColumn, $search_string, $operation, $searchOperator);
            } else {
                $this->applyRegularSearch($query, $model_instance, $searchColumn, $search_string, $operation, $searchOperator, $textColumnTypes);
            }
        }
    }

    /**
     * Apply search for translatable attributes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $model_instance
     * @param  string  $searchColumn
     * @param  string  $search_string
     * @param  string  $operation
     * @param  string  $searchOperator
     * @return void
     */
    private function applyTranslatableSearch($query, $model_instance, $searchColumn, $search_string, $operation, $searchOperator)
    {
        $currentLocale = app()->getLocale();
        $fallbackLocale = config('app.fallback_locale');
        $availableLocales = array_keys(config('backpack.crud.locales', []));
        
        $searchLocales = array_unique(array_filter([
            $currentLocale,
            $fallbackLocale,
            ...$availableLocales
        ]));

        // Use the model's getColumnType method instead of Schema facade
        $columnType = is_string($searchColumn) ? $model_instance->getColumnType($searchColumn) : 'string';
        $isJsonColumn = $this->isJsonColumnType($columnType);
        
        $query->{$operation}(function ($subQuery) use ($searchColumn, $search_string, $searchOperator, $searchLocales, $isJsonColumn) {
            $this->applyLocaleSearchConditions($subQuery, $searchColumn, $search_string, $searchOperator, $searchLocales, $isJsonColumn);
        });
    }

    /**
     * Apply locale-specific search conditions to a query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $searchColumn
     * @param  string  $search_string
     * @param  string  $searchOperator
     * @param  array  $searchLocales
     * @param  bool  $isJsonColumn
     * @return void
     */
    private function applyLocaleSearchConditions($query, $searchColumn, $search_string, $searchOperator, $searchLocales, $isJsonColumn)
    {
        $isFirst = true;
        foreach ($searchLocales as $locale) {
            $localeOperation = $isFirst ? 'where' : 'orWhere';
            $isFirst = false;
            
            if ($searchOperator === 'LIKE' || strtolower($searchOperator) === 'like') {
                $query->$localeOperation(function ($localeQuery) use ($searchColumn, $locale, $search_string, $isJsonColumn) {
                    $this->applyLocaleSearch($localeQuery, $searchColumn, $locale, $search_string, $isJsonColumn);
                });
            } else {
                $query->$localeOperation(function ($localeQuery) use ($searchColumn, $locale, $search_string, $searchOperator, $isJsonColumn) {
                    if ($isJsonColumn) {
                        $localeQuery->whereNotNull("{$searchColumn}->{$locale}")
                                   ->where("{$searchColumn}->{$locale}", $searchOperator, $search_string);
                    } else {
                        // For non-JSON columns, we need to search within the serialized/JSON string
                        $localeQuery->whereRaw("LOWER({$searchColumn}) LIKE LOWER(?)", ['%"' . $locale . '":"' . $search_string . '"%']);
                    }
                });
            }
        }
    }

    /**
     * Check if a column type supports JSON operations.
     *
     * @param  string|null  $columnType
     * @return bool
     */
    private function isJsonColumnType($columnType)
    {
        if (!$columnType) {
            return false;
        }
        
        $jsonTypes = ['json', 'jsonb'];
        return in_array(strtolower($columnType), $jsonTypes);
    }

    /**
     * Apply locale-specific search based on column type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $searchColumn
     * @param  string  $locale
     * @param  string  $search_string
     * @param  bool  $isJsonColumn
     * @return void
     */
    private function applyLocaleSearch($query, $searchColumn, $locale, $search_string, $isJsonColumn)
    {
        if ($isJsonColumn) {
            // Use proper JSON functions for JSON columns
            $query->whereRaw("JSON_EXTRACT({$searchColumn}, '$.{$locale}') IS NOT NULL")
                  ->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT({$searchColumn}, '$.{$locale}'))) LIKE LOWER(?)", ['%' . $search_string . '%']);
        } else {
            // For text columns storing JSON as string, search within the serialized data
            // This handles both PHP serialized arrays and JSON strings
            $query->where(function ($subQuery) use ($searchColumn, $locale, $search_string) {
                // Search for JSON format: "locale":"value"
                $subQuery->whereRaw("LOWER({$searchColumn}) LIKE LOWER(?)", ['%"' . $locale . '":"%' . $search_string . '%"%'])
                         // Also search for PHP serialized format
                         ->orWhereRaw("LOWER({$searchColumn}) LIKE LOWER(?)", ['%s:' . strlen($locale) . ':"' . $locale . '"%' . $search_string . '%']);
            });
        }
    }

    /**
     * Apply search for non-translatable attributes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $model_instance
     * @param  string  $searchColumn
     * @param  string  $search_string
     * @param  string  $operation
     * @param  string  $searchOperator
     * @param  array  $textColumnTypes
     * @return void
     */
    private function applyRegularSearch($query, $model_instance, $searchColumn, $search_string, $operation, $searchOperator, $textColumnTypes)
    {
        $columnType = is_string($searchColumn) ? $model_instance->getColumnType($searchColumn) : 'string';
        
        if (in_array($columnType, $textColumnTypes)) {
            $query->{$operation}($searchColumn, $searchOperator, '%'.$search_string.'%');
        } else {
            $query->{$operation}($searchColumn, $search_string);
        }
    }

    private function processFetchResults(array $config)
    {
        $entries = ($config['paginate'] !== false) ? $config['query']->simplePaginate($config['paginate']) : $config['query']->get();
        if (! isset($config['append_attributes'])) {
            return $entries;
        }

        $config['append_attributes'] = (array) $config['append_attributes'];

        $entries->transform(function ($entry) use ($config) {
            foreach ($config['append_attributes'] as $attribute) {
                $entry->{$attribute} = $entry->{$attribute} ?? null;
            }

            return $entry;
        });

        return $entries;
    }
}
