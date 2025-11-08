<?php

namespace App\Console\Commands;

use App\Models\BaseItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CheckGifs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-gifs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks existence of src files for BaseItems and validates GIF headers';

    public function handle()
    {
        BaseItem::setGlobalConnection('retro');
        $total = BaseItem::on('retro')->count();
        $this->output->progressStart($total);
        BaseItem::on('retro')->chunk(100, function ($items) {
            foreach ($items as $item) {
                $src = 'img/'.$item->src;
                $problem = false;
                $msg = '';
                if (!Storage::disk('s3')->exists($src)) {
                    $problem = true;
                    $msg = "[NOT FOUND] id: {$item->id}, src: {$src}";
                } else if (strtolower(pathinfo($src, PATHINFO_EXTENSION)) === 'gif') {
                    $stream = Storage::disk('s3')->readStream($src);
                    if ($stream === false) {
                        $problem = true;
                        $msg = "[READ ERROR] id: {$item->id}, src: {$src}";
                    } else {
                        $header = fread($stream, 6);
                        fclose($stream);
                        if ($header !== 'GIF87a' && $header !== 'GIF89a') {
                            $problem = true;
                            $msg = "[INVALID GIF] id: {$item->id}, src: {$src}";
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
