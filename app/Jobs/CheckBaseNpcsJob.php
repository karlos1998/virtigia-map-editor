<?php

namespace App\Jobs;

use App\Models\BaseNpc;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class CheckBaseNpcsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $worlds = ['retro', 'legacy'];

    public function handle()
    {
        foreach ($this->worlds as $world) {
            $results = [];
            BaseNpc::setGlobalConnection($world);
            BaseNpc::on($world)->chunk(100, function ($npcs) use (&$results) {
                foreach ($npcs as $npc) {
                    $src = 'img/npc/' . $npc->src;
                    if (!Storage::disk('s3')->exists($src)) {
                        $results[] = [
                            'id' => $npc->id,
                            'src' => $src,
                            'error' => 'NOT FOUND'
                        ];
                    } else if (strtolower(pathinfo($src, PATHINFO_EXTENSION)) === 'gif') {
                        $stream = Storage::disk('s3')->readStream($src);
                        if ($stream === false) {
                            $results[] = [
                                'id' => $npc->id,
                                'src' => $src,
                                'error' => 'READ ERROR'
                            ];
                        } else {
                            $header = fread($stream, 6);
                            fclose($stream);
                            if ($header !== 'GIF87a' && $header !== 'GIF89a') {
                                $results[] = [
                                    'id' => $npc->id,
                                    'src' => $src,
                                    'error' => 'INVALID GIF'
                                ];
                            }
                        }
                    }
                }
            });
            Cache::store('redis')->put("problem_base_npcs_{$world}", json_encode($results), 3600);
        }
    }
}
