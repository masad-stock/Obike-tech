<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ConcurrencyService
{
    /**
     * Run multiple closures concurrently and return their results
     *
     * @param array $closures Array of closures to run
     * @return array Results from each closure in the same order
     */
    public function runConcurrently(array $closures)
    {
        // If we're using Swoole/Octane, we can use coroutines
        if (extension_loaded('swoole') && class_exists('\Swoole\Coroutine')) {
            return $this->runWithSwoole($closures);
        }
        
        // Otherwise, just run them sequentially
        return $this->runSequentially($closures);
    }
    
    /**
     * Run closures using Swoole coroutines
     *
     * @param array $closures
     * @return array
     */
    protected function runWithSwoole(array $closures)
    {
        $results = [];
        $wg = new \Swoole\Coroutine\WaitGroup();
        
        foreach ($closures as $index => $closure) {
            $wg->add();
            
            \Swoole\Coroutine::create(function () use ($closure, $index, &$results, $wg) {
                try {
                    $results[$index] = $closure();
                } catch (\Throwable $e) {
                    Log::error('Error in coroutine: ' . $e->getMessage(), [
                        'exception' => $e,
                    ]);
                    $results[$index] = null;
                } finally {
                    $wg->done();
                }
            });
        }
        
        $wg->wait();
        
        // Ensure results are in the correct order
        ksort($results);
        return array_values($results);
    }
    
    /**
     * Run closures sequentially
     *
     * @param array $closures
     * @return array
     */
    protected function runSequentially(array $closures)
    {
        $results = [];
        
        foreach ($closures as $closure) {
            try {
                $results[] = $closure();
            } catch (\Throwable $e) {
                Log::error('Error in closure: ' . $e->getMessage(), [
                    'exception' => $e,
                ]);
                $results[] = null;
            }
        }
        
        return $results;
    }
}
