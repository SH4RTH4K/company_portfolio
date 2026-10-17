<?php

namespace App\Console\Commands;

use App\Services\SystemBackupService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('system:backup-database')]
#[Description('Create the scheduled database backup and prune expired backups')]
class CreateScheduledSystemBackup extends Command
{
    public function handle(SystemBackupService $backupService): int
    {
        $backup = $backupService->create('database', 'scheduler');
        $prunedCount = $backupService->prune(14);

        $this->info('Created database backup '.$backup->filename.'; removed '.$prunedCount.' expired backup(s).');

        return self::SUCCESS;
    }
}
