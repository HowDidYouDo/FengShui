<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\FloorPlan;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GdprFileDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_customer_deletes_associated_files()
    {
        // Use a directory inside storage/app/testing
        $testPath = storage_path('app/testing_gdpr');

        // Ensure clean state
        if (File::exists($testPath)) {
            File::deleteDirectory($testPath);
        }
        File::makeDirectory($testPath, 0755, true, true);

        // Configure the 'floorplans' disk to use this directory
        config(['filesystems.disks.floorplans.root' => $testPath]);

        // Ensure we start with a fresh instance
        Storage::forgetDisk('floorplans');

        try {
            // 1. Create Data
            $user = User::factory()->create();
            $customer = Customer::create([
                'user_id' => $user->id,
                'name' => 'Test Customer',
            ]);

            $project = Project::create([
                'customer_id' => $customer->id,
                'name' => 'Test Project',
                'settled_year' => 2024,
                'period' => 9,
            ]);

            $floorPlan = FloorPlan::create([
                'project_id' => $project->id,
                'title' => 'Test Floor Plan',
            ]);

            // 2. Attach File
            // Create a real file in our accessible directory
            $sourceFilePath = $testPath.'/source.txt';
            file_put_contents($sourceFilePath, 'test content');

            $file = new UploadedFile(
                $sourceFilePath,
                'floorplan.txt',
                'text/plain',
                null,
                true // test mode
            );

            // Force the disk to be our configured one
            $floorPlan->addMedia($file)->toMediaCollection('floor_plans', 'floorplans');

            $media = $floorPlan->getFirstMedia('floor_plans');
            $this->assertNotNull($media);

            // Verify file exists
            // Media Library puts it in <id>/<filename>
            $relativePath = $media->id.'/'.$file->getClientOriginalName();
            $fullPath = $testPath.'/'.$relativePath;

            $this->assertTrue(File::exists($fullPath), 'File should exist at '.$fullPath);

            // 3. Delete Customer
            $customer->delete();

            // 4. Verify DB Deletion
            $this->assertModelMissing($customer);
            $this->assertModelMissing($project);
            $this->assertModelMissing($floorPlan);

            // 5. Verify File Deletion
            $this->assertFalse(File::exists($fullPath), 'File should be deleted from '.$fullPath);

        } finally {
            // Cleanup
            if (File::exists($testPath)) {
                File::deleteDirectory($testPath);
            }
        }
    }
}
