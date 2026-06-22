<?php

namespace App\Console\Commands;

use App\Models\Group;
use App\Models\Meeting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RenumberMeetings extends Command
{
    protected $signature   = 'meetings:renumber {--group= : Group ID to renumber (omit for all groups)}';
    protected $description = 'Renumber meetings by meeting_date ascending within each group';

    public function handle(): int
    {
        $groups = $this->option('group')
            ? Group::where('id', $this->option('group'))->get()
            : Group::all();

        foreach ($groups as $group) {
            $meetings = Meeting::where('group_id', $group->id)
                ->orderBy('meeting_date')
                ->orderBy('id')
                ->get();

            if ($meetings->isEmpty()) continue;

            $this->info("Group: {$group->name} ({$meetings->count()} meetings)");

            DB::transaction(function () use ($meetings) {
                foreach ($meetings as $index => $meeting) {
                    $newNumber = $index + 1;
                    if ($meeting->meeting_number !== $newNumber) {
                        DB::table('meetings')
                            ->where('id', $meeting->id)
                            ->update(['meeting_number' => $newNumber]);
                        $this->line("  #{$meeting->meeting_number} → #{$newNumber} ({$meeting->meeting_date->format('d/m/Y')})");
                    }
                }
            });
        }

        $this->info('Done.');
        return Command::SUCCESS;
    }
}
