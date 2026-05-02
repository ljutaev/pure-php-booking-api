<?php

declare(strict_types=1);

namespace App\Presentation\Http;

final class RequestValidator
{
    /**
     * Rules format: ['field' => 'required|string', 'count' => 'required|int', 'lat' => 'required|float']
     *
     * @param array<string, mixed>  $body
     * @param array<string, string> $rules
     * @return array<string, string>|null null = valid, array = field errors
     */
    public static function validate(array $body, array $rules): ?array
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $parts    = explode('|', $ruleString);
            $required = in_array('required', $parts, true);

            if (!array_key_exists($field, $body)) {
                if ($required) {
                    $errors[$field] = "The '{$field}' field is required.";
                }

                continue;
            }

            $value = $body[$field];

            if (in_array('string', $parts, true) && (!is_string($value) || trim($value) === '')) {
                $errors[$field] = "The '{$field}' field must be a non-empty string.";
            } elseif (in_array('int', $parts, true) && !is_int($value)) {
                $errors[$field] = "The '{$field}' field must be an integer.";
            } elseif (in_array('float', $parts, true) && !is_float($value) && !is_int($value)) {
                $errors[$field] = "The '{$field}' field must be a number.";
            }
        }

        return $errors === [] ? null : $errors;
    }
}
