<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\CacheService;

class Client extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'company_email',
        'phone',
        'status',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'website',
        'notes',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        // Clear cache when a client is created, updated, or deleted
        static::created(function ($client) {
            self::clearCache();
        });

        static::updated(function ($client) {
            self::clearCache($client->id);
        });

        static::deleted(function ($client) {
            self::clearCache($client->id);
        });
    }

    /**
     * Clear cache related to clients
     *
     * @param int|null $id
     * @return void
     */
    protected static function clearCache($id = null)
    {
        $cacheService = app(CacheService::class);
        
        // Clear specific client cache if ID is provided
        if ($id) {
            $cacheService->forget('client:' . $id);
        }
        
        // Clear general client caches
        $cacheService->forget('clients:list:all');
        $cacheService->forget('clients:list:active');
        $cacheService->forget('clients:list:inactive');
        $cacheService->forget('clients:list:potential');
        
        // Clear dashboard statistics that include client counts
        $cacheService->forget('dashboard:statistics');
    }

    /**
     * Get all projects for this client.
     */
    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get all contacts for this client.
     */
    public function contacts()
    {
        return $this->hasMany(ClientContact::class);
    }

    /**
     * Get all invoices for this client.
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}