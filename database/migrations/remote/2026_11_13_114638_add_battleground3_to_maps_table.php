<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const MODERN_BACKGROUNDS = [
        '002.jpg' => 'm15.jpg',
        '003.jpg' => 'm17.jpg',
        '004.jpg' => 'm13.jpg',
        '005.jpg' => 'm10.jpg',
        '006.jpg' => 'm10.jpg',
        '007.jpg' => 'm11.jpg',
        '07n.jpg' => 'm11.jpg',
        '008.jpg' => 'm13.jpg',
        '009.jpg' => 'm9.jpg',
        '010.jpg' => 'm8.jpg',
        '011.jpg' => 'm5.jpg',
        '012.jpg' => 'm7.jpg',
        '013.jpg' => 'm18.jpg',
        '014.jpg' => 'm6.jpg',
        '015.jpg' => 'm14.jpg',
        '018.jpg' => 'm2.jpg',
        '019.jpg' => 'm1.jpg',
        '19n.jpg' => 'm1.jpg',
        '021.jpg' => 'm5.jpg',
        '027.jpg' => 'm4.jpg',
        '27n.jpg' => 'm17.jpg',
        '032.jpg' => 'm3.jpg',
        '033.jpg' => 'm2.jpg',
        '034.jpg' => 'm1.jpg',
        '035.jpg' => 'm11.jpg',
        '35n.jpg' => 'm11.jpg',
        'aa1.jpg' => 'm16.jpg',
        'aa2.jpg' => 'm16.jpg',
        'bb.jpg' => 'm16.jpg',
        'cc1.jpg' => 'm14.jpg',
        'cc2.jpg' => 'm14.jpg',
        'dd1.jpg' => 'm12.jpg',
        'dd2.jpg' => 'm16.jpg',
        'dd3.jpg' => 'm16.jpg',
        'dd4.jpg' => 'm18.jpg',
        'j.jpg' => 'm15.jpg',
        'ee.jpg' => 'm15.jpg',
        'f.jpg' => 'm15.jpg',
        'g.jpg' => 'm15.jpg',
        'h.jpg' => 'm15.jpg',
        'i.jpg' => 'm12.jpg',
        'k.jpg' => 'm15.jpg',
        'l.jpg' => 'm15.jpg',
        'matchmaking.jpg' => 'm20.jpg',
        '36.jpg' => 'm24.jpg',
        '37.jpg' => 'm26.jpg',
    ];

    public function up(): void
    {
        Schema::table('maps', function (Blueprint $table): void {
            $table->string('battleground3')->nullable()->after('battleground2');
        });

        $battlegrounds = DB::table('maps')->whereNotNull('battleground2')->distinct()->pluck('battleground2');

        foreach ($battlegrounds as $battleground) {
            $path = parse_url(trim($battleground), PHP_URL_PATH);
            $filename = is_string($path) ? strtolower(basename($path)) : '';
            $filename = preg_replace('/\.jpeg$/', '.jpg', $filename);
            // Some keys include a night marker of their own, e.g. 27n.jpg and 27nN.jpg.
            $modern = self::MODERN_BACKGROUNDS[$filename]
                ?? self::MODERN_BACKGROUNDS[preg_replace('/n\.jpg$/', '.jpg', $filename)]
                ?? null;

            if ($modern !== null) {
                DB::table('maps')->where('battleground2', $battleground)->whereNull('battleground3')
                    ->update(['battleground3' => $modern]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('maps', function (Blueprint $table): void {
            $table->dropColumn('battleground3');
        });
    }
};
