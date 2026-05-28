<?php

namespace Tests\Unit\Services;

use App\Services\OAuthPermissionPayload;
use PHPUnit\Framework\TestCase;

class OAuthPermissionPayloadTest extends TestCase
{
    private OAuthPermissionPayload $payload;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payload = new OAuthPermissionPayload();
    }

    public function test_accepts_explicit_map_editor_access_flag(): void
    {
        $this->assertTrue($this->payload->hasMapEditorAccess([
            'map_editor_access' => true,
        ]));
    }

    public function test_rejects_when_explicit_map_editor_access_flag_is_false(): void
    {
        $this->assertFalse($this->payload->hasMapEditorAccess([
            'map_editor_access' => false,
            'permissions' => [
                ['name' => 'map-editor-access'],
            ],
        ]));
    }

    public function test_accepts_legacy_global_permission_payload(): void
    {
        $this->assertTrue($this->payload->hasMapEditorAccess([
            'permissions' => [
                ['name' => 'map-editor-access'],
            ],
        ]));
    }

    public function test_rejects_legacy_scoped_permission_payload(): void
    {
        $this->assertFalse($this->payload->hasMapEditorAccess([
            'permissions' => [
                ['name' => 'map-editor-access', 'world_name' => 'retro'],
            ],
        ]));
    }
}
