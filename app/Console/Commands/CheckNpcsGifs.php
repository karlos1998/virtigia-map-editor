<?php

namespace App\Console\Commands;

use App\Models\BaseNpc;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CheckNpcsGifs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-npcs-gifs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks existence of src files for BaseNpcs and validates GIF headers';

    public function handle()
    {
        BaseNpc::setGlobalConnection('retro');
        $total = BaseNpc::on('retro')->count();
        $this->output->progressStart($total);
        BaseNpc::on('retro')->chunk(100, function ($npcs) {
            foreach ($npcs as $npc) {
                $src = 'img/npc/' . $npc->src;
                $problem = false;
                $msg = '';
                if (!Storage::disk('s3')->exists($src)) {
                    $problem = true;
                    $msg = "[NOT FOUND] id: {$npc->id}, src: {$src}";
                } else if (strtolower(pathinfo($src, PATHINFO_EXTENSION)) === 'gif') {
                    $stream = Storage::disk('s3')->readStream($src);
                    if ($stream === false) {
                        $problem = true;
                        $msg = "[READ ERROR] id: {$npc->id}, src: {$src}";
                    } else {
                        $header = fread($stream, 6);
                        fclose($stream);
                        if ($header !== 'GIF87a' && $header !== 'GIF89a') {
                            $problem = true;
                            $msg = "[INVALID GIF] id: {$npc->id}, src: {$src}";
                        }
                    }
                }
                if ($problem) {
                    $this->error($msg);
                }
                $this->output->progressAdvance();
            }
        });
        $this->output->progressFinish();
    }
}
