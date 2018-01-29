<?php

namespace Nonetallt\LaravelAutoschema;

class SchemaRows
{
    private $class;
    private $model;
    private $attributes;
   
    function __construct(string $class, array $attributes)
    {
        $this->class = $class;
        $this->model = new $class();
        $this->attributes = $attributes;
    }

    function toArray()
    {
        $rows = [];
        foreach($this->attributes as $type => $category) {

            /* Get the callback to resolve the category*/ 
            $cb = $this->cb($category);

            foreach($category as $attributes) {
                
                /* Get properties for this attribute */
                $result = $cb($attribute);

                /* Turn boolean results into strings */
                $rows[] = $this->resolveBool($row);
            }
        }
        return $rows;
    }

    private function resolveBool(array $props)
    {
        $new = [];
        foreach($props as $prop) {

            if(is_string($prop)) $new[] = $prop;
            else $new[] = $prop ? $this->yesString() : $this->noString();
        }
        return $new;
    }

    /* Get the callback to resolve attributes */
    private function cb(string $type)
    {
        $handlers =  [
            'columns' => function($attribute){
                return [
                    'computed' => false,
                    'fillable' => $this->isFillable($attribute),
                    'relation' => false,
                    'serialized' => $this->isSerialized($attribute),
                ];
            },
            'accessors' => function($attribute){
                return [
                    'computed' => $this->isComputed($attribute),
                    'fillable' => $this->isFillable($attribute),
                    'relation' => false,
                    'serialized' => $this->isAppended($attribute),
                ];
            },
            'relations' => function($attribute){
                return [
                    'computed' => false,
                    'fillable' => false,
                    'relation' => true,
                    'serialized' => 'n/a'
                ];
            }
        ];

        return $handlers[$type];
    }

    private function yesString()
    {
        return config('autoschema.yes_string', 'yes');
    }

    private function noString()
    {
        return config('autoschema.no_string', 'no');
    }

    private function isFillable(string $attribute)
    {
        /* Attribute is fillable if it's on the fillable list */
        return $this->model->isFillable($attribute);
    }

    private function isComputed(string $attribute)
    {
        /* Attribute is computed if it does not exist in database */
        $columns = \Schema::getColumnListing($this->model->getTable());

        return ! in_array($attribute, $columns);
    }

    private function isSerialized(string $attribute)
    {
        return ! in_array($attribute, $this->model->getHidden());
    }

    private function isAppended(string $attribute)
    {
        $prop = (new \ReflectionClass($this->class))->getProperty('appends');
        $prop->setAccessible(true);
        $appended = $prop->getValue(($this->class));
        return in_array($attribute, $appended);
    }
}


