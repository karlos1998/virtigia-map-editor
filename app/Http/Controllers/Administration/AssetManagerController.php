<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssetOperationRequest;
use App\Http\Requests\BrowseAssetsRequest;
use App\Http\Requests\CreateAssetDirectoryRequest;
use App\Http\Requests\UploadAssetRequest;
use App\Services\AssetManagerService;
use App\Services\AssetOperationService;
use App\Services\AssetThumbnailService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetManagerController extends Controller
{
    public function __construct(private readonly AssetManagerService $assets, private readonly AssetOperationService $operations) {}

    public function index(BrowseAssetsRequest $request): Response
    {
        return Inertia::render('Administration/Assets/Index', [
            'initialPath' => $request->assetPath(),
            'initialSearch' => $request->searchTerm(),
            'maxUploadBytes' => AssetManagerService::maxUploadBytes(),
        ]);
    }

    public function listing(BrowseAssetsRequest $request): JsonResponse
    {
        return response()->json($this->assets->browse($request->assetPath(), $request->searchTerm(), $request->pageNumber(), $request->boolean('directories_only')));
    }

    public function createDirectory(CreateAssetDirectoryRequest $request): JsonResponse
    {
        $this->assets->createDirectory($request->assetPath(), $request->directoryName());

        return response()->json(['message' => 'Katalog został utworzony.'], 201);
    }

    public function upload(UploadAssetRequest $request): JsonResponse
    {
        $this->assets->upload($request->assetPath(), $request->assetFile());

        return response()->json(['message' => 'Plik został wysłany.'], 201);
    }

    public function preview(BrowseAssetsRequest $request): StreamedResponse
    {
        return $this->assets->fileResponse($request->assetPath(), true);
    }

    public function thumbnail(BrowseAssetsRequest $request, AssetThumbnailService $thumbnails): BinaryFileResponse
    {
        return $thumbnails->response($request->assetPath());
    }

    public function download(BrowseAssetsRequest $request): StreamedResponse
    {
        return $this->assets->fileResponse($request->assetPath(), false);
    }

    public function startOperation(AssetOperationRequest $request): JsonResponse
    {
        return response()->json($this->operations->start((int) $request->user()->id, $request->operationData()), 202);
    }

    public function operations(BrowseAssetsRequest $request): JsonResponse
    {
        return response()->json($this->operations->recent((int) $request->user()->id));
    }

    public function downloadArchive(BrowseAssetsRequest $request, string $operation): BinaryFileResponse
    {
        return $this->operations->download($operation, (int) $request->user()->id);
    }
}
