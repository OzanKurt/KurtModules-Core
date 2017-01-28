<?php

namespace Kurt\Modules\Core;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

class Links implements \ArrayAccess, Arrayable, Jsonable
{

    private $model;

    private $links = [];

    private $resourceKeys = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];

    function __construct($model, $links)
    {
        $this->model = $model;

        $this->links = collect($links);

        $this->buildLinks();
    }

    public function buildLinks()
    {
        $this->links->transform(function($link, $key) {

            $parameters = $this->findParameters($link);

            foreach ($parameters as $parameter) {

                $newParameter = $this->findReplacement($parameter);

                $link = $this->replace($parameter, $newParameter, $link);
            }

            return $link;
        });
    }

    public function replace($old, $new, $string)
    {
        return str_replace('{'.$old.'}', $new, $string);
    }

    public function findParameters($link)
    {
        preg_match_all("/\{(.*?)\}/", $link, $parameters);

        return $parameters[1];
    }

    public function findReplacement($parameter)
    {
        $properties = explode('->', $parameter);

        $new = $this->model;

        foreach ($properties as $property) {

            if (method_exists($new, $property)) {
                if (!$new->relationLoaded($property)) {
                    $new->load($property);
                }
            }

            $new = $new->{$property};
        }

        return $new;
    }

    public function __get($property)
    {
        if (in_array($property, $this->resourceKeys)) {
            return $this->links->get($property);
        }

        throw new \Exception("Property is not a resourceful route key.");
    }

    public function offsetExists($offset) {
        return $this->links->has($offset);
    }

    public function offsetGet($offset) {
        return $this->links->get($offset);
    }

    public function offsetSet ($offset, $value) {
        // ...
    }

    public function offsetUnset($offset) {
        // ...
    }

    public function __toString()
    {
        return $this->links->toJson();
    }

    public function toJson($options = 0)
    {
        return $this->links->toJson($options);
    }

    public function toArray()
    {
        return $this->links->toArray();
    }

}