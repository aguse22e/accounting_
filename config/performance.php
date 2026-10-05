<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Performance Optimization Settings
    |--------------------------------------------------------------------------
    |
    | This file contains performance optimization settings for the application.
    |
    */

    'cache' => [
        'master_data_ttl' => 3600, // 1 hour cache for master data
        'query_cache_ttl' => 1800,  // 30 minutes cache for queries
    ],

    'database' => [
        'query_limit' => 50, // Limit queries to prevent memory issues
        'chunk_size' => 100, // Chunk size for large data processing
    ],

    'view' => [
        'compile_views' => true, // Compile views for better performance
        'cache_views' => true,    // Cache compiled views
    ],

    'optimization' => [
        'eager_loading' => true,  // Enable eager loading by default
        'select_columns' => true, // Select only needed columns
        'use_indexes' => true,    // Use database indexes
    ],
];
