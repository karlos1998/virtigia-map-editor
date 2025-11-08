<?php

namespace App\Jobs;

use App\Models\BaseNpc;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class CheckBaseNpcsChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    protected string $world;
    protected int $chunkIndex;
    protected int $chunkSize;

    public function __construct($world, $chunkIndex, $chunkSize)
    {
        $this->world = $world;
        $this->chunkIndex = $chunkIndex;
        $this->chunkSize = $chunkSize;
    }

    public function handle()
    {
        BaseNpc::setGlobalConnection($this->world);
        $npcs = BaseNpc::on($this->world)
            ->skip($this->chunkIndex * $this->chunkSize)
            ->take($this->chunkSize)
            ->get();
        $results = [];
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
        Cache::store('redis')->put("problem_base_npcs_{$this->world}_chunk_{$this->chunkIndex}", json_encode($results), 3600);
    }
}
