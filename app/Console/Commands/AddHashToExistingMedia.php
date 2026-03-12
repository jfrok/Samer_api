<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AddHashToExistingMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:add-hashes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add SHA-256 hash to existing media files for duplicate detection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Adding hashes to existing media files...');

        $media = Media::whereNull('hash')->get();
        $total = $media->count();

        if ($total === 0) {
            $this->info('All media files already have hashes!');
            return 0;
        }

        $this->info("Found {$total} media files without hashes.");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $processed = 0;
        $failed = 0;

        foreach ($media as $item) {
            try {
                $path = $item->getPath();

                if (file_exists($path)) {
                    $hash = hash_file('sha256', $path);

                    // Mark as original upload if not already set
                    $customProps = $item->custom_properties;
                    if (!isset($customProps['is_original_upload'])) {
                        $customProps['is_original_upload'] = true;
                    }

                    $item->hash = $hash;
                    $item->custom_properties = $customProps;
                    $item->save();

                    $processed++;
                } else {
                    $this->error("\nFile not found: {$path}");
                    $failed++;
                }
            } catch (\Exception $e) {
                $this->error("\nError processing media ID {$item->id}: {$e->getMessage()}");
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();

        $this->newLine();
        $this->info("Successfully processed: {$processed}");

        if ($failed > 0) {
            $this->warn("Failed: {$failed}");
        }

        $this->info('Done!');

        return 0;
    }
}
