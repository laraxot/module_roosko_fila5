<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Modules\Waste\Enums\HazardPropertyEnum;
use Spatie\QueueableAction\QueueableAction;

/**
 * Normalizza le caratteristiche di pericolo lette da un modello Waste (json: enum, stringhe
 * `HP4`... o collection) in `list<HazardPropertyEnum>`, ordinata e senza duplicati.
 */
class CastHazardPropertiesAction
{
    use QueueableAction;

    /**
     * @return list<HazardPropertyEnum>
     */
    public function execute(mixed $value): array
    {
        if ($value instanceof \Traversable) {
            $value = iterator_to_array($value, false);
        }
        if (! \is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            $case = $item instanceof HazardPropertyEnum ? $item : (\is_string($item) ? HazardPropertyEnum::tryFrom($item) : null);
            if ($case !== null) {
                $result[$case->value] = $case;
            }
        }
        ksort($result, SORT_NATURAL);

        return array_values($result);
    }
}
