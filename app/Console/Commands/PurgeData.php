<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeData extends Command
{
    protected $signature = 'data:purge {--keep-users : Keep user accounts} {--keep-templates : Keep templates and custom fields}';
    protected $description = 'Purge all data from the database while keeping structure';

    public function handle()
    {
        $keepUsers = $this->option('keep-users');
        $keepTemplates = $this->option('keep-templates');
        
        $this->displayWarning($keepUsers, $keepTemplates);
        
        if (!$this->confirm('Are you sure you want to continue?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info('Purging data...');

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Truncate data tables
        DB::table('template_field_values')->truncate();
        $this->line('✓ Cleared template_field_values');
        
        if (!$keepTemplates) {
            DB::table('template_fields')->truncate();
            $this->line('✓ Cleared template_fields');
            
            DB::table('column_mapping_templates')->truncate();
            $this->line('✓ Cleared column_mapping_templates');
        } else {
            $this->line('⊘ Keeping template_fields');
            $this->line('⊘ Keeping column_mapping_templates');
        }
        
        DB::table('match_results')->truncate();
        $this->line('✓ Cleared match_results');
        
        DB::table('upload_batches')->truncate();
        $this->line('✓ Cleared upload_batches');
        
        DB::table('main_system')->truncate();
        $this->line('✓ Cleared main_system');

        if (!$keepUsers) {
            DB::table('users')->truncate();
            $this->line('✓ Cleared users');
            
            DB::table('password_reset_tokens')->truncate();
            $this->line('✓ Cleared password_reset_tokens');
            
            DB::table('sessions')->truncate();
            $this->line('✓ Cleared sessions');
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->newLine();
        $this->info('✅ Database purged successfully!');
        
        if (!$keepUsers) {
            $this->warn('You will need to register a new account.');
        }

        return 0;
    }

    /**
     * Display warning message based on options
     */
    private function displayWarning(bool $keepUsers, bool $keepTemplates): void
    {
        $items = [];
        
        if (!$keepUsers) {
            $items[] = 'user accounts';
        }
        
        if (!$keepTemplates) {
            $items[] = 'templates and custom fields';
        }
        
        if (empty($items)) {
            $this->warn('⚠️  This will delete all data except user accounts and templates!');
        } else {
            $this->warn('⚠️  This will delete ALL data including ' . implode(' and ', $items) . '!');
        }
    }
}
