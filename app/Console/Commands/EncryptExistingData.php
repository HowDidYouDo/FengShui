<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\FamilyMember;
use App\Services\Metaphysics\MingGuaCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class EncryptExistingData extends Command
{
    protected $signature = 'app:encrypt-data {--old-key= : The old APP_KEY if it was changed} {--force : Run without confirmation}';
    protected $description = 'Encrypts existing plaintext data and re-encrypts old encrypted data using direct DB updates to bypass Eloquent decryption errors.';

    private ?Encrypter $oldEncrypter = null;
    private ?MingGuaCalculator $guaCalculator = null;

    public function handle()
    {
        if ($oldKey = $this->option('old-key')) {
            try {
                if (str_starts_with($oldKey, 'base64:')) {
                    $oldKey = base64_decode(substr($oldKey, 7));
                }
                $this->oldEncrypter = new Encrypter($oldKey, config('app.cipher'));
                $this->info('Using provided old APP_KEY for decryption attempts.');
            } catch (\Exception $e) {
                $this->error('The provided old key is invalid: ' . $e->getMessage());
                return 1;
            }
        }

        $this->guaCalculator = app(MingGuaCalculator::class);

        if (!$this->option('force') && !$this->confirm('This will iterate over all customers and family members and re-encrypt their data via direct DB updates. Do you want to proceed?')) {
            return 0;
        }

        $this->info('Starting data migration...');

        // Configuration for Customer
        $this->migrateDirectly('customers', [
            'birth_date' => 'no_serialize',
            'birth_place' => 'serialize',
            'billing_street' => 'serialize'
        ], true);

        // Configuration for FamilyMember
        $this->migrateDirectly('family_members', [
            'birth_date' => 'no_serialize',
            'birth_place' => 'serialize'
        ], true);

        $this->info('Migration completed.');
        return 0;
    }

    protected function migrateDirectly(string $table, array $fields, bool $handleGua = false)
    {
        $count = DB::table($table)->count();
        $this->info("Processing $count records for table $table...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        DB::table($table)->orderBy('id')->chunk(100, function ($records) use ($table, $fields, $bar, $handleGua) {
            foreach ($records as $record) {
                $updates = [];
                $needsGuaUpdate = false;

                foreach ($fields as $field => $type) {
                    $rawValue = $record->{$field};
                    if ($rawValue === null || $rawValue === '') continue;

                    $decrypted = $this->attemptDecryption($rawValue);

                    if ($decrypted !== null) {
                        // Re-encrypt with CURRENT settings
                        $updates[$field] = ($type === 'no_serialize') 
                            ? encrypt($decrypted, false) 
                            : encrypt($decrypted, true);
                        
                        if ($field === 'birth_date') $needsGuaUpdate = true;
                    }
                }

                if ($handleGua && $needsGuaUpdate && isset($record->gender)) {
                    $guaData = $this->calculateGua($record, $updates);
                    if ($guaData) {
                        $updates = array_merge($updates, $guaData);
                    }
                }

                if (!empty($updates)) {
                    $updates['updated_at'] = now();
                    DB::table($table)->where('id', $record->id)->update($updates);
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
    }

    private function calculateGua($record, $updates)
    {
        // Use new birth_date if updated, otherwise old one (decrypted)
        $dateRaw = $updates['birth_date'] ?? $record->birth_date;
        $gender = $record->gender;

        if (!$dateRaw || !$gender) return null;

        try {
            // Get decrypted date for calculation
            $dateStr = $this->attemptDecryption($dateRaw) ?: $dateRaw;
            $date = Carbon::parse($dateStr);
            
            $solarYear = $this->guaCalculator->getSolarYear($date);
            $gua = $this->guaCalculator->calculate($solarYear, $gender);

            return [
                'life_gua' => $gua,
                'kua_group' => strtolower($this->guaCalculator->getAttributes($gua)['group'] ?? '')
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    private function attemptDecryption($value)
    {
        if (!is_string($value)) return null;

        // Sequence of attempts
        $attempts = [
            fn() => decrypt($value, true),
            fn() => decrypt($value, false),
        ];

        if ($this->oldEncrypter) {
            $attempts[] = fn() => $this->oldEncrypter->decrypt($value, true);
            $attempts[] = fn() => $this->oldEncrypter->decrypt($value, false);
        }

        foreach ($attempts as $attempt) {
            try {
                $decrypted = $attempt();
                if ($this->isSerialized($decrypted)) {
                    return @unserialize($decrypted);
                }
                return $decrypted;
            } catch (\Exception $e) {}
        }

        // Plaintext fallback
        if (!str_starts_with($value, 'eyJpdi')) {
            return $value;
        }

        return null;
    }

    private function isSerialized($data): bool
    {
        if (!is_string($data)) return false;
        $data = trim($data);
        return ($data === 'N;' || preg_match('/^([adObis]):/', $data));
    }
}
