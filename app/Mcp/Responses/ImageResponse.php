<?php

namespace App\Mcp\Responses;

use App\Mcp\Content\ImageContent;
use Laravel\Mcp\Response;

class ImageResponse extends Response
{
    public static function fromBinary(string $binary, string $mimeType = 'image/png'): static
    {
        return new static(new ImageContent($binary, $mimeType));
    }
}
