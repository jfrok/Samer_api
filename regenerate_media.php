<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\MediaLibrary\MediaCollections\Models\Media;

// Get media ID from command line or use 22
$mediaId = $argv[1] ?? 22;

$media = Media::find($mediaId);

if (!$media) {
    echo "Media ID {$mediaId} not found\n";
    exit(1);
}

echo "Regenerating conversions for Media ID: {$mediaId}\n";
echo "File: {$media->file_name}\n";
echo "Collection: {$media->collection_name}\n\n";

try {
    // Get the model that owns this media
    $model = $media->model;

    if (!$model) {
        echo "Model not found\n";
        exit(1);
    }

    echo "Model: " . get_class($model) . " (ID: {$model->id})\n";

    // Reset generated conversions in database
    \DB::table('media')
        ->where('id', $mediaId)
        ->update([
            'generated_conversions' => json_encode([]),
            'manipulations' => json_encode([])
        ]);

    echo "\n🔄 Performing conversions...\n";

    // Use the FileManipulator to perform conversions
    $fileManipulator = app(\Spatie\MediaLibrary\Conversions\FileManipulator::class);
    $fileManipulator->createDerivedFiles($media);

    // Refresh to see updated generated_conversions
    $media->refresh();

    echo "\n✅ Conversions generated:\n";
    if (is_array($media->generated_conversions) && count($media->generated_conversions) > 0) {
        foreach ($media->generated_conversions as $name => $status) {
            echo "- {$name}: " . ($status ? '✓' : '✗') . "\n";
        }
    } else {
        echo "⚠️  No conversions generated. Check model configuration.\n";
        echo "Generated conversions value: " . json_encode($media->generated_conversions) . "\n";
    }

    echo "\n✅ Done!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
