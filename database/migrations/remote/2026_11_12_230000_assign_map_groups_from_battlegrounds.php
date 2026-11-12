<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Gives every map a starting group based on its battle background (battleground2),
 * e.g. "004N.jpg" -> "004" -> bad_caves. Maps already moved out of no_group keep their group.
 */
return new class extends Migration
{
    private const GROUPS_BY_BATTLEGROUND = [
        '002' => 'bad_caves',
        '003' => 'caves',
        '004' => 'bad_caves',
        '006' => 'bad_caves',
        '013' => 'caves',
        '014' => 'caves',
        '015' => 'caves',
        '027' => 'bad_caves',
        '27n' => 'bad_caves',
        '032' => 'bad_fields',
        '007' => 'fields',
        '07n' => 'fields',
        '018' => 'desert',
        '019' => 'sea',
        '19n' => 'sea',
        '035' => 'fields',
        '35n' => 'fields',
        '005' => 'bad_fields',
        '008' => 'bad_fields',
        '009' => 'bad_fields',
        '010' => 'fields',
        '011' => 'fields',
        '012' => 'bad_fields',
        '021' => 'fields',
        '033' => 'desert',
        '034' => 'sea',
        'aa1' => 'no_group',
        'aa2' => 'no_group',
        'bb' => 'no_group',
        'cc1' => 'bad_insides',
        'cc2' => 'bad_insides',
        'dd1' => 'bad_insides',
        'dd2' => 'bad_insides',
        'dd3' => 'bad_insides',
        'dd4' => 'bad_insides',
        'ee' => 'insides',
        'f' => 'no_group',
        'g' => 'no_group',
        'h' => 'no_group',
        'i' => 'bad_insides',
        'j' => 'insides',
        'k' => 'insides',
        'l' => 'insides',
    ];

    public function up(): void
    {
        $battlegrounds = DB::table('maps')
            ->where('map_group', 'no_group')
            ->whereNotNull('battleground2')
            ->distinct()
            ->pluck('battleground2');

        foreach ($battlegrounds as $battleground) {
            $group = self::GROUPS_BY_BATTLEGROUND[$this->backgroundKey($battleground)] ?? null;

            if ($group === null || $group === 'no_group') {
                continue;
            }

            DB::table('maps')
                ->where('map_group', 'no_group')
                ->where('battleground2', $battleground)
                ->update(['map_group' => $group]);
        }
    }

    public function down(): void
    {
        // Groups may have been edited since; there is nothing safe to restore.
    }

    /**
     * "004N.jpg" -> "004", "27nN.jpg" -> "27n".
     */
    private function backgroundKey(string $battleground): string
    {
        return strtolower((string) preg_replace('/N\.jpe?g$/i', '', trim($battleground)));
    }
};
