<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateInput
{
    public function handle(Request $request, Closure $next)
    {
        // Global input sanitization
        $input = $request->all();
        array_walk_recursive($input, function(&$value) {
            if (is_string($value)) {
                $value = trim($value);
                // Additional sanitization as needed
            }
        });
        
        $request->replace($input);
        
        return $next($request);
    }
}