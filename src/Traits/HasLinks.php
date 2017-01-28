<?php

namespace Kurt\Modules\Core\Traits;

trait HasLinks
{

    public function getLinksAttribute()
    {
        $links = [];

        foreach (static::$routes as $key => $value) {

            preg_match_all("/\{(.*?)\}/", $value, $routeParameters);

            foreach ($routeParameters[1] as $routeParameter) {

                $properties = explode('->', $routeParameter);

                $temp = $this;
                foreach ($properties as $property) {
                    $temp = $temp->{$property};
                }

                $value = str_replace('{'.$routeParameter.'}', $temp, $value);
            }

            $links[$key] = $value;
        }

        return $links;
    }
    
}
