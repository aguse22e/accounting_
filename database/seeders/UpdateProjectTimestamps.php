<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UpdateProjectTimestamps extends Seeder
{
    /**
     * Run the seeder - update existing project timestamps to have realistic times
     * (not just midnight 00:00)
     */
    public function run(): void
    {
        $projects = DB::table('projects')->get();
        
        foreach ($projects as $project) {
            // For each project, set a realistic time (random within last 30 days)
            $hoursAgo = rand(0, 720);  // Last 30 days
            $minutesAgo = rand(0, 59);
            $time = now()->subHours($hoursAgo)->subMinutes($minutesAgo);
            
            DB::table('projects')
                ->where('id', $project->id)
                ->update(['tanggal' => $time]);
        }
        
        $this->command->info('Updated ' . count($projects) . ' project timestamps with realistic times!');
    }
}
