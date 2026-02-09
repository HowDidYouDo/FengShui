<?php

namespace App\Traits;

use App\Services\Metaphysics\MingGuaCalculator;

trait HasMingGua
{
    /**
     * Boot the trait.
     */
    public static function bootHasMingGua(): void
    {
        static::saving(function ($model) {
            if ($model->isDirty(['birth_date', 'gender'])) {
                $model->calculateGuaAttributes();
            }
        });
    }

    /**
     * Berechnet die Gua-Attribute basierend auf Geburtsdatum und Geschlecht.
     */
    public function calculateGuaAttributes(): void
    {
        if ($this->birth_date && $this->gender) {
            try {
                $calculator = app(MingGuaCalculator::class);

                $date = $this->birth_date;
                $solarYear = $calculator->getSolarYear($date);
                $gua = $calculator->calculate($solarYear, $this->gender);

                $this->life_gua = $gua;
                $this->kua_group = strtolower($calculator->getAttributes($gua)['group'] ?? '');
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Fehler bei der Gua-Berechnung für ' . get_class($this) . ' ID: ' . $this->id . ': ' . $e->getMessage());
            }
        } else {
            $this->life_gua = null;
            $this->kua_group = null;
        }
    }
}
