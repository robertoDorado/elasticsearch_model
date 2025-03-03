<?php
if (!function_exists('transformCamelCaseToSnakeCase')) {
    function transformCamelCaseToSnakeCase(array $args)
    {
        foreach ($args as &$originalString) {
            $transformedString = preg_replace('/([a-z])([A-Z])/', '$1_$2', $originalString);
            $originalString = strtolower($transformedString);
        }
        return $args;
    }
}

if (!function_exists('multiMatchTypeData')) {
    function multiMatchTypeData() {
        return [
            'best_fields',
            'most_fields',
            'cross_fields',
            'phrase',
            'phrase_prefix'
        ];
    }
}

if (!function_exists('isNumericFormat')) {
    function isNumericFormat(string $value) {
        return !preg_match('/\D/', $value) ? true : false;
    }
}

if (!function_exists('propertiesType')) {
    function propertiesType() {
        return [
            'text' => true,
            'keyword' => true,
            'long' => true,
            'integer' => true,
            'short' => true,
            'byte' => true,
            'double' => true,
            'float' => true,
            'half_float' => true,
            'scaled_float' => true,
            'boolean' => true,
            'date' => true,
            'date_nanos' => true,
            'object' => true,
            'nested' => true,
            'ip' => true,
            'geo_point' => true,
            'geo_shape' => true,
            'completion' => true
        ];
    }
}