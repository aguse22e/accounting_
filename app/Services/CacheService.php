<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    /**
     * Clear all master data cache
     */
    public static function clearMasterDataCache()
    {
        Cache::forget('akuns_all');
        Cache::forget('akun_list_all');
        Cache::forget('kode_akun_all');
        Cache::forget('projects_all');
    }

    /**
     * Clear specific cache by key
     */
    public static function clearCache($key)
    {
        Cache::forget($key);
    }

    /**
     * Get cached data with fallback
     */
    public static function remember($key, $ttl, $callback)
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Clear all application cache
     */
    public static function clearAllCache()
    {
        Cache::flush();
    }
}
