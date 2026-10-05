<?php

namespace App\Helpers;

class TransactionHelper
{
    /**
     * Normalize transaction type from Indonesian/English to database ENUM format
     * Database ENUM: 'debit' | 'credit'
     * 
     * @param string $type Input type (Debit, debit, Kredit, kredit, Credit, credit)
     * @return string Normalized type ('debit' or 'credit')
     */
    public static function normalizeType(string $type): string
    {
        $typeMapping = [
            'Debit' => 'debit',
            'debit' => 'debit',
            'DEBIT' => 'debit',
            'Kredit' => 'credit',
            'kredit' => 'credit',
            'KREDIT' => 'credit',
            'Credit' => 'credit',
            'credit' => 'credit',
            'CREDIT' => 'credit',
        ];
        
        return $typeMapping[$type] ?? strtolower($type);
    }
    
    /**
     * Convert database type to Indonesian display format
     * 
     * @param string $type Database type ('debit' or 'credit')
     * @return string Display format ('Debit' or 'Kredit')
     */
    public static function toIndonesian(string $type): string
    {
        $typeMapping = [
            'debit' => 'Debit',
            'credit' => 'Kredit',
        ];
        
        return $typeMapping[strtolower($type)] ?? $type;
    }
}
