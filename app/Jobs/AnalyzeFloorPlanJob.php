<?php

namespace App\Jobs;

use App\Models\FloorPlan;
use App\Services\Metaphysics\MingGuaCalculator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnalyzeFloorPlanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Konfiguration für den Service
    protected string $apiUrl = 'http://127.0.0.1:8090';

    public function __construct(
        public FloorPlan $floorPlan
    )
    {
    }

    public function handle(): void
    {
        // 1. Validierung: Hat der Plan ein Bild?
        if (!$this->floorPlan->hasMedia('floor_plans')) {
            Log::warning("FloorPlan {$this->floorPlan->id} analysis skipped: No media found.");
            return;
        }


        // FAKE MODE für lokale Entwicklung ohne Python API
        // Wenn API_MOCK=true in .env steht ODER der Health Check fehlschlägt
        if (config('app.env') === 'local' || !$this->checkApiHealth()) {

            Log::info("FloorPlan {$this->floorPlan->id}: Using MOCK analysis data.");

            // Simuliere eine kurze Wartezeit
            sleep(2);

            // Fake Bounds (Zentriertes Quadrat)
            $fakeBounds = [
                'x1' => 100,
                'y1' => 100,
                'x2' => 900, // 800px breit
                'y2' => 700, // 600px hoch
                'image_width' => 1000,
                'image_height' => 800,
            ];

            $this->floorPlan->update(['outer_bounds' => $fakeBounds]);
            return;
        }

        // 3. Vorbereitung des Uploads
        $mediaItem = $this->floorPlan->getFirstMedia('floor_plans');
        $imagePath = $mediaItem->getPath();
        $fileName = $mediaItem->file_name;

        try {
            Log::info("Sending FloorPlan {$this->floorPlan->id} to Python API...");

            // 4. Der eigentliche Request
            $response = Http::timeout(60) // 60s Timeout für große Bilder
            ->attach(
                'file', // Feldname muss zur FastAPI passen (def 'file')
                file_get_contents($imagePath),
                $fileName
            )
                ->post("{$this->apiUrl}/process");

            // 5. Verarbeitung der Antwort
            if ($response->successful()) {
                $data = $response->json();

                // Validierung der Antwort-Struktur
                if (isset($data['rectangle']) && isset($data['dimensions'])) {

                    $boundsData = [
                        'x1' => (float)$data['rectangle']['x1'],
                        'y1' => (float)$data['rectangle']['y1'],
                        'x2' => (float)$data['rectangle']['x2'],
                        'y2' => (float)$data['rectangle']['y2'],
                        'image_width' => (float)$data['dimensions']['width'],
                        'image_height' => (float)$data['dimensions']['height'],
                    ];

                    $this->floorPlan->update([
                        'outer_bounds' => $boundsData
                    ]);

                    Log::info("FloorPlan {$this->floorPlan->id} analysis completed successfully.");

                    $baguaCalculator = app(MingGuaCalculator::class);
                    $baguaResult = $baguaCalculator->calculateClassicalBaguaForFloorPlan(
                        $this->floorPlan,
                        $this->floorPlan->project->ventilation_direction ?? 0
                    );

                    Log::info("Classical Bagua calculated", [
                        'floor_plan_id' => $this->floorPlan->id,
                        'sitz_gua' => $baguaResult['sitz_gua'],
                        'notes_count' => $baguaResult['notes_count']
                    ]);
                } else {
                    Log::error("FloorPlan {$this->floorPlan->id}: Invalid JSON structure from API.", ['response' => $data]);
                }
            } else {
                Log::error("FloorPlan {$this->floorPlan->id}: API returned error {$response->status()}.", ['body' => $response->body()]);
            }

        } catch (\Exception $e) {
            Log::error("FloorPlan {$this->floorPlan->id}: Exception during analysis. " . $e->getMessage());
            $this->fail($e); // Markiert den Job als fehlgeschlagen
        }
    }

    /**
     * Prüft, ob der Python Service antwortet (GET /health)
     */
    private function checkApiHealth(): bool
    {
        try {
            $response = Http::timeout(3)->get("{$this->apiUrl}/health");

            if ($response->successful()) {
                $data = $response->json();
                return ($data['status'] ?? '') === 'ok';
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
