<?php

namespace App\Jobs;

use App\Models\BaseItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class CheckBaseItemsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $worlds = ['retro', 'legacy'];

    public function handle()
    {
        foreach ($this->worlds as $world) {
            $results = [];
            BaseItem::setGlobalConnection($world);
            BaseItem::on($world)->chunk(100, function ($items) use (&$results) {
                foreach ($items as $item) {
                    $src = 'img/' . $item->src;
                    if (!Storage::disk('s3')->exists($src)) {
                        $results[] = [
                            'id' => $item->id,
                            'src' => $src,
                            'error' => 'NOT FOUND'
                        ];
                    } else if (strtolower(pathinfo($src, PATHINFO_EXTENSION)) === 'gif') {
                        $stream = Storage::disk('s3')->readStream($src);
                        if ($stream === false) {
                            $results[] = [
                                'id' => $item->id,
                                'src' => $src,
                                'error' => 'READ ERROR'
                            ];
                        } else {
                            $header = fread($stream, 6);
                            fclose($stream);
                            if ($header !== 'GIF87a' && $header !== 'GIF89a') {
                                $results[] = [
                                    'id' => $item->id,
                                    'src' => $src,
                                    'error' => 'INVALID GIF'
                                ];
                            }
                        }
                    }
                }
            });
            Cache::store('redis')->put("problem_base_items_{$world}", json_encode($results), 3600);
        }
    }
}
