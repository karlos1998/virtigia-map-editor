<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, shallowRef, watch } from 'vue';

export interface MapImageExport {
    dataUrl: string;
    fileName: string;
    pixelWidth: number;
    pixelHeight: number;
    tileWidth: number;
    tileHeight: number;
}

const emit = defineEmits<{
    readyChange: [ready: boolean];
}>();

const TILE_SIZE = 32;
const MAX_TILES_PER_SIDE = 128;
const MAX_PREVIEW_HEIGHT = 680;

const previewCanvas = ref<HTMLCanvasElement | null>(null);
const previewShell = ref<HTMLElement | null>(null);
const sourceImage = shallowRef<HTMLImageElement | null>(null);
const sourceObjectUrl = ref<string | null>(null);
const sourceWidth = ref(0);
const sourceHeight = ref(0);
const tileWidth = ref(1);
const tileHeight = ref(1);
const zoomPercent = ref(100);
const offsetX = ref(0);
const offsetY = ref(0);
const fileName = ref('mapa.png');
const selectionError = ref('');
const shellWidth = ref(0);
const hoverTile = ref<{ x: number; y: number } | null>(null);
const isDragging = ref(false);
const dragStart = ref({ pointerX: 0, pointerY: 0, offsetX: 0, offsetY: 0 });

let resizeObserver: ResizeObserver | null = null;
let drawingFrame: number | null = null;

const outputPixelWidth = computed(() => tileWidth.value * TILE_SIZE);
const outputPixelHeight = computed(() => tileHeight.value * TILE_SIZE);
const minimumZoom = computed(() => {
    if (!sourceImage.value) {
        return 10;
    }

    return Math.max(
        outputPixelWidth.value / sourceWidth.value,
        outputPixelHeight.value / sourceHeight.value,
    ) * 100;
});
const maximumZoom = computed(() => Math.max(400, Math.ceil(minimumZoom.value * 4)));
const isReady = computed(() => sourceImage.value !== null && /^[a-zA-Z0-9_-]+\.png$/i.test(fileName.value));
const scaleDescription = computed(() => {
    if (!sourceImage.value) {
        return '';
    }

    const renderedWidth = Math.round(sourceWidth.value * zoomPercent.value / 100);
    const renderedHeight = Math.round(sourceHeight.value * zoomPercent.value / 100);

    return `${renderedWidth} × ${renderedHeight} px`;
});

watch(isReady, (ready) => emit('readyChange', ready), { immediate: true });
watch([tileWidth, tileHeight], () => {
    if (!sourceImage.value) {
        return;
    }

    zoomPercent.value = Math.max(zoomPercent.value, Math.ceil(minimumZoom.value * 10) / 10);
    centerImage();
});
watch([zoomPercent, offsetX, offsetY, shellWidth, hoverTile], scheduleDraw, { deep: true });

onMounted(() => {
    resizeObserver = new ResizeObserver(([entry]) => {
        shellWidth.value = Math.floor(entry.contentRect.width);
        scheduleDraw();
    });

    if (previewShell.value) {
        resizeObserver.observe(previewShell.value);
    }
});

onBeforeUnmount(() => {
    resizeObserver?.disconnect();

    if (sourceObjectUrl.value) {
        URL.revokeObjectURL(sourceObjectUrl.value);
    }

    if (drawingFrame !== null) {
        cancelAnimationFrame(drawingFrame);
    }
});

function onFileSelect(event: { files?: File[] }): void {
    const file = event.files?.[0];

    if (!file) {
        return;
    }

    if (!['image/png', 'image/jpeg'].includes(file.type)) {
        selectionError.value = 'Wybierz grafikę PNG albo JPEG.';

        return;
    }

    selectionError.value = '';

    if (sourceObjectUrl.value) {
        URL.revokeObjectURL(sourceObjectUrl.value);
    }

    sourceObjectUrl.value = URL.createObjectURL(file);
    const image = new Image();

    image.onload = () => {
        sourceImage.value = image;
        sourceWidth.value = image.naturalWidth;
        sourceHeight.value = image.naturalHeight;
        tileWidth.value = Math.min(MAX_TILES_PER_SIDE, Math.max(1, Math.floor(image.naturalWidth / TILE_SIZE)));
        tileHeight.value = Math.min(MAX_TILES_PER_SIDE, Math.max(1, Math.floor(image.naturalHeight / TILE_SIZE)));
        fileName.value = sanitizeFileName(file.name);
        zoomPercent.value = Math.max(100, Math.ceil(minimumZoom.value * 10) / 10);
        centerImage();
        nextTick(scheduleDraw);
    };
    image.onerror = () => {
        selectionError.value = 'Nie udało się odczytać wybranej grafiki.';
        sourceImage.value = null;
    };
    image.src = sourceObjectUrl.value;
}

function sanitizeFileName(originalName: string): string {
    const baseName = originalName.replace(/\.[^.]+$/, '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-zA-Z0-9_-]+/g, '_')
        .replace(/^_+|_+$/g, '') || 'mapa';

    return `${baseName}.png`;
}

function setTileWidth(value: number | null): void {
    tileWidth.value = Math.min(MAX_TILES_PER_SIDE, Math.max(1, Math.round(value ?? 1)));
}

function setTileHeight(value: number | null): void {
    tileHeight.value = Math.min(MAX_TILES_PER_SIDE, Math.max(1, Math.round(value ?? 1)));
}

function centerImage(): void {
    if (!sourceImage.value) {
        return;
    }

    const scale = zoomPercent.value / 100;
    offsetX.value = (outputPixelWidth.value - sourceWidth.value * scale) / 2;
    offsetY.value = (outputPixelHeight.value - sourceHeight.value * scale) / 2;
    clampOffsets();
    scheduleDraw();
}

function fitImage(): void {
    zoomPercent.value = Math.ceil(minimumZoom.value * 10) / 10;
    centerImage();
}

function setZoom(value: number, pivotX = outputPixelWidth.value / 2, pivotY = outputPixelHeight.value / 2): void {
    if (!sourceImage.value) {
        return;
    }

    const oldScale = zoomPercent.value / 100;
    const nextZoom = Math.min(maximumZoom.value, Math.max(minimumZoom.value, value));
    const nextScale = nextZoom / 100;
    const sourcePointX = (pivotX - offsetX.value) / oldScale;
    const sourcePointY = (pivotY - offsetY.value) / oldScale;

    zoomPercent.value = Math.round(nextZoom * 10) / 10;
    offsetX.value = pivotX - sourcePointX * nextScale;
    offsetY.value = pivotY - sourcePointY * nextScale;
    clampOffsets();
    scheduleDraw();
}

function clampOffsets(): void {
    if (!sourceImage.value) {
        return;
    }

    const scaledWidth = sourceWidth.value * zoomPercent.value / 100;
    const scaledHeight = sourceHeight.value * zoomPercent.value / 100;

    offsetX.value = Math.min(0, Math.max(outputPixelWidth.value - scaledWidth, offsetX.value));
    offsetY.value = Math.min(0, Math.max(outputPixelHeight.value - scaledHeight, offsetY.value));
}

function previewDimensions(): { width: number; height: number } {
    const availableWidth = Math.max(280, shellWidth.value || 900);
    const ratio = outputPixelWidth.value / outputPixelHeight.value;
    let width = availableWidth;
    let height = width / ratio;

    if (height > MAX_PREVIEW_HEIGHT) {
        height = MAX_PREVIEW_HEIGHT;
        width = height * ratio;
    }

    return { width: Math.max(1, Math.floor(width)), height: Math.max(1, Math.floor(height)) };
}

function scheduleDraw(): void {
    if (drawingFrame !== null) {
        cancelAnimationFrame(drawingFrame);
    }

    drawingFrame = requestAnimationFrame(() => {
        drawingFrame = null;
        drawPreview();
    });
}

function drawPreview(): void {
    const canvas = previewCanvas.value;
    const image = sourceImage.value;

    if (!canvas || !image) {
        return;
    }

    const dimensions = previewDimensions();
    const deviceScale = window.devicePixelRatio || 1;
    const outputScale = dimensions.width / outputPixelWidth.value;
    const renderScale = outputScale * deviceScale;

    canvas.style.width = `${dimensions.width}px`;
    canvas.style.height = `${dimensions.height}px`;
    canvas.width = Math.max(1, Math.round(dimensions.width * deviceScale));
    canvas.height = Math.max(1, Math.round(dimensions.height * deviceScale));

    const context = canvas.getContext('2d');

    if (!context) {
        return;
    }

    context.setTransform(renderScale, 0, 0, renderScale, 0, 0);
    context.clearRect(0, 0, outputPixelWidth.value, outputPixelHeight.value);
    context.fillStyle = '#111827';
    context.fillRect(0, 0, outputPixelWidth.value, outputPixelHeight.value);
    context.imageSmoothingEnabled = true;
    context.imageSmoothingQuality = 'high';

    const imageScale = zoomPercent.value / 100;
    context.drawImage(
        image,
        offsetX.value,
        offsetY.value,
        sourceWidth.value * imageScale,
        sourceHeight.value * imageScale,
    );

    drawGrid(context, outputScale);
}

function drawGrid(context: CanvasRenderingContext2D, outputScale: number): void {
    const lineWidth = 1 / Math.max(outputScale, 0.001);
    context.lineWidth = lineWidth;
    context.strokeStyle = 'rgba(255, 255, 255, 0.42)';
    context.beginPath();

    for (let x = 0; x <= outputPixelWidth.value; x += TILE_SIZE) {
        context.moveTo(x, 0);
        context.lineTo(x, outputPixelHeight.value);
    }

    for (let y = 0; y <= outputPixelHeight.value; y += TILE_SIZE) {
        context.moveTo(0, y);
        context.lineTo(outputPixelWidth.value, y);
    }

    context.stroke();

    if (hoverTile.value) {
        context.fillStyle = 'rgba(59, 130, 246, 0.28)';
        context.fillRect(hoverTile.value.x * TILE_SIZE, hoverTile.value.y * TILE_SIZE, TILE_SIZE, TILE_SIZE);
        context.lineWidth = 2 / Math.max(outputScale, 0.001);
        context.strokeStyle = '#60a5fa';
        context.strokeRect(hoverTile.value.x * TILE_SIZE, hoverTile.value.y * TILE_SIZE, TILE_SIZE, TILE_SIZE);
    }

    const displayedTileSize = TILE_SIZE * outputScale;
    const labelEvery = Math.max(1, Math.ceil(44 / Math.max(displayedTileSize, 1)));
    const fontSize = 11 / Math.max(outputScale, 0.001);
    const padding = 3 / Math.max(outputScale, 0.001);
    context.font = `600 ${fontSize}px sans-serif`;
    context.textBaseline = 'top';

    for (let x = 0; x < tileWidth.value; x += labelEvery) {
        drawGridLabel(context, String(x), x * TILE_SIZE + padding, padding, fontSize, padding);
    }

    for (let y = labelEvery; y < tileHeight.value; y += labelEvery) {
        drawGridLabel(context, String(y), padding, y * TILE_SIZE + padding, fontSize, padding);
    }
}

function drawGridLabel(
    context: CanvasRenderingContext2D,
    label: string,
    x: number,
    y: number,
    fontSize: number,
    padding: number,
): void {
    const metrics = context.measureText(label);
    context.fillStyle = 'rgba(17, 24, 39, 0.78)';
    context.fillRect(x - padding, y - padding, metrics.width + padding * 2, fontSize + padding * 2);
    context.fillStyle = '#ffffff';
    context.fillText(label, x, y);
}

function canvasPoint(event: PointerEvent | WheelEvent): { x: number; y: number } {
    const canvas = previewCanvas.value;

    if (!canvas) {
        return { x: 0, y: 0 };
    }

    const rectangle = canvas.getBoundingClientRect();

    return {
        x: (event.clientX - rectangle.left) * outputPixelWidth.value / rectangle.width,
        y: (event.clientY - rectangle.top) * outputPixelHeight.value / rectangle.height,
    };
}

function onPointerDown(event: PointerEvent): void {
    if (!sourceImage.value || !previewCanvas.value) {
        return;
    }

    const point = canvasPoint(event);
    isDragging.value = true;
    dragStart.value = {
        pointerX: point.x,
        pointerY: point.y,
        offsetX: offsetX.value,
        offsetY: offsetY.value,
    };
    previewCanvas.value.setPointerCapture(event.pointerId);
}

function onPointerMove(event: PointerEvent): void {
    const point = canvasPoint(event);
    hoverTile.value = {
        x: Math.min(tileWidth.value - 1, Math.max(0, Math.floor(point.x / TILE_SIZE))),
        y: Math.min(tileHeight.value - 1, Math.max(0, Math.floor(point.y / TILE_SIZE))),
    };

    if (!isDragging.value) {
        return;
    }

    offsetX.value = dragStart.value.offsetX + point.x - dragStart.value.pointerX;
    offsetY.value = dragStart.value.offsetY + point.y - dragStart.value.pointerY;
    clampOffsets();
}

function onPointerUp(event: PointerEvent): void {
    isDragging.value = false;
    previewCanvas.value?.releasePointerCapture(event.pointerId);
}

function onPointerLeave(): void {
    if (!isDragging.value) {
        hoverTile.value = null;
    }
}

function onWheel(event: WheelEvent): void {
    if (!sourceImage.value) {
        return;
    }

    event.preventDefault();
    const point = canvasPoint(event);
    const multiplier = event.deltaY < 0 ? 1.1 : 0.9;
    setZoom(zoomPercent.value * multiplier, point.x, point.y);
}

async function exportImage(): Promise<MapImageExport | null> {
    const image = sourceImage.value;

    if (!image || !isReady.value) {
        return null;
    }

    const canvas = document.createElement('canvas');
    canvas.width = outputPixelWidth.value;
    canvas.height = outputPixelHeight.value;
    const context = canvas.getContext('2d');

    if (!context) {
        throw new Error('Nie udało się utworzyć obrazu wynikowego.');
    }

    context.imageSmoothingEnabled = true;
    context.imageSmoothingQuality = 'high';
    const imageScale = zoomPercent.value / 100;
    context.drawImage(
        image,
        offsetX.value,
        offsetY.value,
        sourceWidth.value * imageScale,
        sourceHeight.value * imageScale,
    );

    return {
        dataUrl: canvas.toDataURL('image/png'),
        fileName: fileName.value,
        pixelWidth: outputPixelWidth.value,
        pixelHeight: outputPixelHeight.value,
        tileWidth: tileWidth.value,
        tileHeight: tileHeight.value,
    };
}

defineExpose({ exportImage });
</script>

<template>
    <section class="flex flex-col gap-5">
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
            <div class="flex flex-wrap items-center gap-3">
                <FileUpload
                    mode="basic"
                    custom-upload
                    auto
                    accept="image/png,image/jpeg"
                    :max-file-size="50000000"
                    choose-label="Wybierz grafikę mapy"
                    severity="secondary"
                    class="p-button-outlined"
                    @select="onFileSelect"
                />
                <p class="text-sm text-gray-600">
                    Możesz użyć grafiki o dowolnym rozmiarze. Poniżej dopasujesz ją do siatki 32×32 px.
                </p>
            </div>
            <Message v-if="selectionError" severity="error" class="mt-3">{{ selectionError }}</Message>
        </div>

        <template v-if="sourceImage">
            <div class="grid gap-4 xl:grid-cols-[minmax(18rem,23rem)_minmax(0,1fr)]">
                <aside class="flex flex-col gap-5 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Obszar wynikowy</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Źródło: {{ sourceWidth }} × {{ sourceHeight }} px
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex flex-col gap-2 text-sm font-medium text-gray-700">
                            Szerokość w polach
                            <InputNumber
                                :model-value="tileWidth"
                                :min="1"
                                :max="MAX_TILES_PER_SIDE"
                                show-buttons
                                fluid
                                @update:model-value="setTileWidth"
                            />
                        </label>
                        <label class="flex flex-col gap-2 text-sm font-medium text-gray-700">
                            Wysokość w polach
                            <InputNumber
                                :model-value="tileHeight"
                                :min="1"
                                :max="MAX_TILES_PER_SIDE"
                                show-buttons
                                fluid
                                @update:model-value="setTileHeight"
                            />
                        </label>
                    </div>

                    <div class="rounded-lg bg-blue-50 p-3 text-sm text-blue-950">
                        <div class="font-semibold">{{ tileWidth }} × {{ tileHeight }} pól</div>
                        <div>{{ outputPixelWidth }} × {{ outputPixelHeight }} px po zapisaniu</div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <div class="flex items-center justify-between gap-3">
                            <label for="map-zoom" class="text-sm font-medium text-gray-700">Skala grafiki</label>
                            <span class="text-sm tabular-nums text-gray-600">{{ zoomPercent.toFixed(1) }}%</span>
                        </div>
                        <input
                            id="map-zoom"
                            :value="zoomPercent"
                            type="range"
                            :min="minimumZoom"
                            :max="maximumZoom"
                            step="0.1"
                            class="w-full accent-blue-600"
                            @input="setZoom(Number(($event.target as HTMLInputElement).value))"
                        >
                        <p class="text-xs text-gray-500">Rozmiar grafiki po skalowaniu: {{ scaleDescription }}</p>
                        <div class="flex flex-wrap gap-2">
                            <Button label="Wypełnij kadr" icon="pi pi-expand" size="small" severity="secondary" @click="fitImage" />
                            <Button label="Wyśrodkuj" icon="pi pi-align-center" size="small" severity="secondary" outlined @click="centerImage" />
                        </div>
                    </div>

                    <label class="flex flex-col gap-2 text-sm font-medium text-gray-700">
                        Nazwa pliku wynikowego
                        <InputText v-model="fileName" />
                    </label>
                    <Message v-if="!isReady" severity="warn" size="small">
                        Nazwa pliku może zawierać litery, cyfry, „_” i „-” oraz musi kończyć się na .png.
                    </Message>

                    <div class="rounded-lg border border-gray-200 p-3 text-sm text-gray-600">
                        <div class="font-medium text-gray-800">Sterowanie</div>
                        <div class="mt-1">Przeciągnij grafikę, aby zmienić kadr. Kółko myszy skaluje względem kursora.</div>
                    </div>
                </aside>

                <div class="min-w-0 rounded-xl border border-gray-700 bg-gray-950 p-3 shadow-lg">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2 px-1 text-sm text-gray-300">
                        <span>Podgląd z siatką 32×32 px</span>
                        <span v-if="hoverTile" class="rounded bg-gray-800 px-2 py-1 font-mono">
                            Pole X: {{ hoverTile.x }}, Y: {{ hoverTile.y }} · px {{ hoverTile.x * 32 }}, {{ hoverTile.y * 32 }}
                        </span>
                    </div>
                    <div ref="previewShell" class="flex min-h-80 w-full items-center justify-center overflow-hidden rounded-lg bg-gray-900">
                        <canvas
                            ref="previewCanvas"
                            class="max-w-full select-none"
                            :class="isDragging ? 'cursor-grabbing' : 'cursor-grab'"
                            style="touch-action: none"
                            @pointerdown="onPointerDown"
                            @pointermove="onPointerMove"
                            @pointerup="onPointerUp"
                            @pointercancel="onPointerUp"
                            @pointerleave="onPointerLeave"
                            @wheel="onWheel"
                        />
                    </div>
                </div>
            </div>
        </template>
    </section>
</template>
