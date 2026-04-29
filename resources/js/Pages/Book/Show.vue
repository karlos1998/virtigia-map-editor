<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import ItemHeader from "@/Components/ItemHeader.vue";
import { route } from "ziggy-js";
import { useForm } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";
import axios from "axios";
import { computed, ref, watch } from "vue";

const props = defineProps<{
    book: {
        id: number
        title: string
        content: string
    }
}>();

const toast = useToast();

const form = useForm({
    title: props.book.title,
    content: props.book.content,
});

const save = () => {
    form.patch(route("books.update", { book: props.book.id }), {
        onSuccess: () => {
            toast.add({ severity: "success", summary: "Zapisano", detail: "Książka została zaktualizowana", life: 3000 });
        },
    });
};

type PreviewNpc = {
    id: number
    name: string
    lvl: number
    profession: string
    profession_name: string
    rank: string
    rank_name: string
    src: string
};

type PreviewItem = {
    id: number
    name: string
    src: string
    rarity: string
    category: string
    category_name: string
    price: number
    need_level: number
    attributes?: Record<string, unknown>
};

type ItemRenderBlock = {
    items: PreviewItem[]
    mode: "text" | "img"
    format: string
    separator: string
    align: "left" | "center"
};

type NpcLoopEntry = {
    npc: PreviewNpc
    npcMode: "text" | "img"
    npcFormat: string
    npcAlign: "left" | "center"
    itemBlocks: ItemRenderBlock[]
};

type PreviewBlock =
    | { type: "html"; html: string }
    | { type: "npcs"; items: PreviewNpc[]; mode: "text" | "img"; format: string; separator: string }
    | { type: "items"; items: PreviewItem[]; mode: "text" | "img"; format: string; separator: string }
    | { type: "npc-loop-rich"; entries: NpcLoopEntry[] };

const previewPages = ref<PreviewBlock[][]>([]);
const previewPageIndex = ref(0);
const showAllPreviewPages = ref(false);

const totalPreviewPages = computed(() => Math.max(1, previewPages.value.length));
const visiblePreviewPages = computed(() => {
    if (showAllPreviewPages.value) {
        return previewPages.value;
    }
    return [previewPages.value[Math.max(0, Math.min(previewPageIndex.value, previewPages.value.length - 1))] || []];
});

const escapeHtml = (value: string): string =>
    value
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#39;");

const parseAttrs = (raw: string): Record<string, string> => {
    const attrs: Record<string, string> = {};
    const regex = /(\w+)\s*=\s*("[^"]+"|'[^']+'|[^\s]+)/g;
    let match: RegExpExecArray | null;
    while ((match = regex.exec(raw)) !== null) {
        attrs[match[1]] = String(match[2] ?? "").replace(/^['"]|['"]$/g, "");
    }
    return attrs;
};

const renderBasicBbcode = (source: string): string => {
    return escapeHtml(source)
        .replace(/\[b\]([\s\S]*?)\[\/b\]/gi, "<strong>$1</strong>")
        .replace(/\[i\]([\s\S]*?)\[\/i\]/gi, "<em>$1</em>")
        .replace(/\[u\]([\s\S]*?)\[\/u\]/gi, "<u>$1</u>")
        .replace(/\[url=(.+?)\]([\s\S]*?)\[\/url\]/gi, '<a href="$1" target="_blank" rel="noopener noreferrer">$2</a>')
        .replace(/\[color=(.+?)\]([\s\S]*?)\[\/color\]/gi, '<span style="color:$1">$2</span>')
        .replace(/\r?\n/g, "<br>");
};

const applyNpcFormat = (format: string, npc: PreviewNpc): string => {
    return format
        .replaceAll("{id}", String(npc.id))
        .replaceAll("{name}", npc.name)
        .replaceAll("{lvl}", String(npc.lvl))
        .replaceAll("{profession}", npc.profession)
        .replaceAll("{profession_name}", npc.profession_name)
        .replaceAll("{rank}", npc.rank)
        .replaceAll("{rank_name}", npc.rank_name)
        .replaceAll("{src}", npc.src);
};

const applyItemFormat = (format: string, item: PreviewItem): string => {
    return format
        .replaceAll("{id}", String(item.id))
        .replaceAll("{name}", item.name)
        .replaceAll("{src}", item.src)
        .replaceAll("{rarity}", item.rarity)
        .replaceAll("{category}", item.category)
        .replaceAll("{category_name}", item.category_name)
        .replaceAll("{price}", String(item.price))
        .replaceAll("{need_level}", String(item.need_level));
};

const separatorClass = (separator: string): string => {
    if (separator === "comma") return "comma";
    if (separator === "space") return "space";
    return "line";
};

const alignClass = (align: string): string => (align === "center" ? "center" : "left");

const fetchNpcs = async (params: Record<string, unknown>): Promise<{ items: PreviewNpc[]; meta: { page: number; last_page: number; total: number; per_page: number } }> => {
    const { data } = await axios.get(route("books.preview.base-npcs"), { params });
    return {
        items: (data?.items || []) as PreviewNpc[],
        meta: data?.meta || { page: 1, last_page: 1, total: 0, per_page: 30 },
    };
};

const fetchItems = async (params: Record<string, unknown>): Promise<PreviewItem[]> => {
    const { data } = await axios.get(route("books.preview.base-items"), { params });
    return (data?.items || []) as PreviewItem[];
};

const processSingleTag = async (tag: string, attrsRaw: string): Promise<PreviewBlock> => {
    const attrs = parseAttrs(attrsRaw);
    const mode = (attrs.mode === "img" ? "img" : "text") as "text" | "img";
    const separator = attrs.separator || "line";

    if (tag === "npc") {
        const singleId = Number(attrs.id || attrsRaw.trim().replace(/^=/, "").trim());
        const ids = attrs.ids ? attrs.ids.split(",").map((v) => Number(v.trim())).filter(Boolean) : [];
        if (singleId > 0) ids.push(singleId);
        const response = await fetchNpcs({ ids: Array.from(new Set(ids)) });
        return {
            type: "npcs",
            items: response.items,
            mode,
            format: attrs.format || "{name} ({lvl}{profession})",
            separator,
        };
    }

    if (tag === "npc-loop") {
        const response = await fetchNpcs({
            rank: attrs.rank,
            lvl_from: attrs.minLvl || attrs.lvlFrom,
            lvl_to: attrs.maxLvl || attrs.lvlTo,
            per_page: attrs.perPage || attrs.limit || 10,
            page: attrs.page || 1,
        });

        return {
            type: "npcs",
            items: response.items,
            mode,
            format: attrs.format || "{name} ({lvl}{profession})",
            separator,
        };
    }

    const items = await fetchItems({
        rarity: attrs.rarity,
        lvl_from: attrs.minLvl || attrs.lvlFrom,
        lvl_to: attrs.maxLvl || attrs.lvlTo,
        base_npc_ids: attrs.baseNpcIds ? attrs.baseNpcIds.split(",").map((v) => Number(v.trim())).filter(Boolean) : [],
        per_page: attrs.perPage || attrs.limit || 10,
        page: attrs.page || 1,
    });

    return {
        type: "items",
        items,
        mode,
        format: attrs.format || "{name} ({rarity}, lvl {need_level})",
        separator,
    };
};

const fetchAllNpcsForLoop = async (attrs: Record<string, string>): Promise<PreviewNpc[]> => {
    const merged: PreviewNpc[] = [];
    let page = 1;

    while (true) {
        const response = await fetchNpcs({
            rank: attrs.rank,
            lvl_from: attrs.minLvl || attrs.lvlFrom,
            lvl_to: attrs.maxLvl || attrs.lvlTo,
            per_page: 50,
            page,
        });

        merged.push(...response.items);
        const lastPage = Number(response.meta?.last_page || 1);
        if (page >= lastPage) break;
        page += 1;
    }

    return merged;
};

const processNpcLoopRich = async (attrsRaw: string, inner: string): Promise<{ entries: NpcLoopEntry[]; perPage: number }> => {
    const attrs = parseAttrs(attrsRaw);
    const perPage = Math.max(1, Number(attrs.perPage || attrs.limit || 10));
    const npcs = await fetchAllNpcsForLoop(attrs);

    const npcTagMatch = inner.match(/\[npc([^[\]]*)\]/i);
    const npcTagAttrs = npcTagMatch ? parseAttrs(npcTagMatch[1] || "") : {};
    const npcMode = (npcTagAttrs.mode === "img" ? "img" : attrs.mode === "img" ? "img" : "text") as "text" | "img";
    const npcFormat = npcTagAttrs.format || attrs.format || "{name} ({lvl}{profession})";
    const npcAlign = (npcTagAttrs.align === "center" || attrs.align === "center" ? "center" : "left") as "left" | "center";

    const itemLoopRegex = /\[item-loop([^\]]*)\]/gi;
    const itemLoopDefs = Array.from(inner.matchAll(itemLoopRegex)).map((m) => parseAttrs(m[1] || ""));

    const entries: NpcLoopEntry[] = [];
    for (const npc of npcs) {
        const itemBlocks: ItemRenderBlock[] = [];
        for (const itemAttrs of itemLoopDefs) {
            const items = await fetchItems({
                base_npc_ids: [npc.id],
                rarity: itemAttrs.rarity,
                lvl_from: itemAttrs.minLvl || itemAttrs.lvlFrom,
                lvl_to: itemAttrs.maxLvl || itemAttrs.lvlTo,
                per_page: itemAttrs.perPage || itemAttrs.limit || 20,
                page: itemAttrs.page || 1,
            });

            itemBlocks.push({
                items,
                mode: itemAttrs.mode === "img" ? "img" : "text",
                format: itemAttrs.format || "{name} ({rarity})",
                separator: itemAttrs.separator || "line",
                align: itemAttrs.align === "center" ? "center" : "left",
            });
        }

        entries.push({
            npc,
            npcMode,
            npcFormat,
            npcAlign,
            itemBlocks,
        });
    }

    return { entries, perPage };
};

const buildPreview = async () => {
    const source = form.content || "";
    const finalPages: PreviewBlock[][] = [];
    const sourcePages = source.split(/\[page\]/gi);

    const processInlineSegment = async (segment: string, blocks: PreviewBlock[]) => {
        const inlineRegex = /\[((?:npc(?:-loop)?|item-loop))([^\]]*)\]/gi;
        let inlineLast = 0;
        let inlineMatch: RegExpExecArray | null;

        while ((inlineMatch = inlineRegex.exec(segment)) !== null) {
            const start = inlineMatch.index;
            if (start > inlineLast) {
                const chunk = segment.slice(inlineLast, start);
                if (chunk.trim() !== "") {
                    blocks.push({ type: "html", html: renderBasicBbcode(chunk) });
                }
            }

            const tag = (inlineMatch[1] || "").toLowerCase();
            const attrsRaw = inlineMatch[2] || "";
            blocks.push(await processSingleTag(tag, attrsRaw));
            inlineLast = start + inlineMatch[0].length;
        }

        if (inlineLast < segment.length) {
            const chunk = segment.slice(inlineLast);
            if (chunk.trim() !== "") {
                blocks.push({ type: "html", html: renderBasicBbcode(chunk) });
            }
        }
    };

    for (let pageIdx = 0; pageIdx < sourcePages.length; pageIdx += 1) {
        const pageContent = sourcePages[pageIdx] || "";
        const pageBlocks: PreviewBlock[] = [];
        const richLoopRegex = /\[npc-loop([^\]]*)\]([\s\S]*?)\[\/npc-loop\]/gi;
        let lastIndex = 0;
        let richMatch: RegExpExecArray | null;

        while ((richMatch = richLoopRegex.exec(pageContent)) !== null) {
            const start = richMatch.index;
            if (start > lastIndex) {
                await processInlineSegment(pageContent.slice(lastIndex, start), pageBlocks);
            }

            const attrsRaw = richMatch[1] || "";
            const inner = richMatch[2] || "";
            const loopResolved = await processNpcLoopRich(attrsRaw, inner);

            const totalLoopPages = Math.max(1, Math.ceil(loopResolved.entries.length / loopResolved.perPage));
            const introBlocks = [...pageBlocks];

            for (let loopPageIdx = 0; loopPageIdx < totalLoopPages; loopPageIdx += 1) {
                const from = loopPageIdx * loopResolved.perPage;
                const to = from + loopResolved.perPage;
                const slicedEntries = loopResolved.entries.slice(from, to);
                const scopedBlocks: PreviewBlock[] = [];

                if (loopPageIdx === 0) {
                    scopedBlocks.push(...introBlocks);
                }

                if (slicedEntries.length > 0) {
                    scopedBlocks.push({
                        type: "npc-loop-rich",
                        entries: slicedEntries,
                    });
                }

                if (scopedBlocks.length > 0) {
                    finalPages.push(scopedBlocks);
                }
            }

            pageBlocks.length = 0;
            lastIndex = start + richMatch[0].length;
        }

        if (lastIndex < pageContent.length) {
            await processInlineSegment(pageContent.slice(lastIndex), pageBlocks);
        }

        if (pageBlocks.length > 0) {
            finalPages.push(pageBlocks);
        }
    }

    previewPages.value = finalPages.length ? finalPages : [[{ type: "html", html: "" }]];
    previewPageIndex.value = Math.max(0, Math.min(previewPageIndex.value, previewPages.value.length - 1));
};

const prevPreviewPage = () => {
    if (previewPageIndex.value > 0) {
        previewPageIndex.value -= 1;
    }
};

const nextPreviewPage = () => {
    if (previewPageIndex.value < totalPreviewPages.value - 1) {
        previewPageIndex.value += 1;
    }
};

watch(
    () => form.content,
    () => {
        buildPreview().catch(() => {
            previewPages.value = [[{ type: "html", html: '<div class="book-npc-missing">Błąd podglądu BBCode.</div>' }]];
        });
    },
    { immediate: true }
);

const exampleSnippets = computed(() => [
    {
        title: "Pojedynczy NPC (tekst)",
        code: '[npc id=818 mode=text format="{name} ({lvl}{profession})"]',
    },
    {
        title: "Pętla NPC jako obrazki z tipem",
        code: '[npc-loop rank=ELITE_II minLvl=10 maxLvl=50 perPage=4 page=1 mode=img]',
    },
    {
        title: "Pętla przedmiotów (np. unique od 50 lvl)",
        code: '[item-loop rarity=unique minLvl=50 perPage=20 page=1 mode=text format="{name} ({rarity}, lvl {need_level})"]',
    },
    {
        title: "Zagnieżdżony npc-loop: NPC + itemy tego NPC",
        code: '[npc-loop rank=ELITE_II minLvl=10 maxLvl=50 perPage=3 page=1]\n[npc mode=img]\n[item-loop rarity=unique mode=text format="{name} ({rarity})"]\n[/npc-loop]',
    },
    {
        title: "Zagnieżdżony npc-loop: tekst NPC + ładne itemy z v-tip",
        code: '[npc-loop rank=HERO minLvl=1 maxLvl=200 perPage=2 page=1]\n[npc mode=text format="{name} - {rank_name} ({lvl}{profession})"]\n[item-loop mode=img rarity=legendary]\n[/npc-loop]',
    },
    {
        title: "NPC (v-tip) wyśrodkowany + itemy jeden pod drugim, też na środku",
        code: '[npc-loop rank=ELITE_II minLvl=10 maxLvl=50 perPage=3 page=1]\n[npc mode=img align=center]\n[item-loop mode=img rarity=unique align=center separator=line]\n[/npc-loop]',
    },
    {
        title: "Wstęp na 1. stronie, a od 2. strony lista NPC",
        code: 'Witaj w spisie mobów.\nTu znajdziesz listę ELIT II do 50 poziomu.\n[page]\n[npc-loop rank=ELITE_II minLvl=10 maxLvl=50 perPage=3]\n[npc mode=img align=center]\n[item-loop mode=img rarity=unique align=center separator=line]\n[/npc-loop]',
    },
]);
</script>

<template>
    <AppLayout>
        <ItemHeader :route-back="route('books.index')" route-back-label="Powrót do listy">
            <template #header>
                Książka #{{ book.id }}
            </template>
            <template #right-buttons>
                <Button label="Zapisz" icon="pi pi-save" @click="save" :loading="form.processing" />
            </template>
        </ItemHeader>

        <div class="card">
            <div class="mb-4">
                <label class="font-semibold block mb-2">Tytuł</label>
                <InputText v-model="form.title" class="w-full" />
            </div>
            <div>
                <label class="font-semibold block mb-2">Treść (BBCode, podział stron: [page])</label>
                <Textarea v-model="form.content" rows="20" class="w-full" autoResize />
            </div>
        </div>

        <div class="card">
            <h3 class="mb-3">Podgląd książki (live)</h3>
            <div class="book-preview-toolbar">
                <div class="book-preview-pagination">
                    <Button label="<" size="small" :disabled="showAllPreviewPages || previewPageIndex <= 0" @click="prevPreviewPage" />
                    <span>Strona {{ previewPageIndex + 1 }} / {{ totalPreviewPages }}</span>
                    <Button label=">" size="small" :disabled="showAllPreviewPages || previewPageIndex >= totalPreviewPages - 1" @click="nextPreviewPage" />
                </div>
                <label class="book-preview-toggle">
                    <input type="checkbox" v-model="showAllPreviewPages">
                    <span>Pokaż wszystkie strony (jedna pod drugą)</span>
                </label>
            </div>

            <div v-for="(pageBlocks, pageIdx) in visiblePreviewPages" :key="`preview-page-${pageIdx}-${showAllPreviewPages ? pageIdx : previewPageIndex}`" class="book-preview">
                <div v-if="showAllPreviewPages" class="book-preview-page-title">Strona {{ pageIdx + 1 }}</div>
                <template v-for="(block, idx) in pageBlocks" :key="`block-${pageIdx}-${idx}`">
                    <div v-if="block.type === 'html'" v-html="block.html" />

                    <div v-else-if="block.type === 'npcs'" class="book-npc-block" :class="separatorClass(block.separator)">
                        <template v-if="block.items.length === 0">
                            <span class="book-npc-missing">Brak wyników</span>
                        </template>

                        <template v-else-if="block.mode === 'img'">
                            <img
                                v-for="npc in block.items"
                                :key="npc.id"
                                :src="npc.src"
                                :alt="npc.name"
                                class="book-npc-img"
                                v-tip.npc="npc"
                            />
                        </template>

                        <template v-else>
                            <span v-for="(npc, i) in block.items" :key="npc.id" class="book-npc-text">
                                {{ applyNpcFormat(block.format, npc) }}<span v-if="block.separator === 'comma' && i < block.items.length - 1">, </span><span v-else-if="block.separator === 'space' && i < block.items.length - 1"> </span>
                            </span>
                        </template>
                    </div>

                    <div v-else-if="block.type === 'items'" class="book-npc-block" :class="separatorClass(block.separator)">
                        <template v-if="block.items.length === 0">
                            <span class="book-npc-missing">Brak przedmiotów</span>
                        </template>

                        <template v-else-if="block.mode === 'img'">
                            <img
                                v-for="item in block.items"
                                :key="item.id"
                                :src="item.src"
                                :alt="item.name"
                                class="book-npc-img"
                                v-tip.item.top.show-id="item"
                            />
                        </template>

                        <template v-else>
                            <span v-for="(item, i) in block.items" :key="item.id" class="book-npc-text">
                                {{ applyItemFormat(block.format, item) }}<span v-if="block.separator === 'comma' && i < block.items.length - 1">, </span><span v-else-if="block.separator === 'space' && i < block.items.length - 1"> </span>
                            </span>
                        </template>
                    </div>

                    <div v-else class="book-rich-loop">
                        <div v-for="entry in block.entries" :key="entry.npc.id" class="book-rich-entry">
                            <div class="book-npc-block line" :class="alignClass(entry.npcAlign)">
                                <img
                                    v-if="entry.npcMode === 'img'"
                                    :src="entry.npc.src"
                                    :alt="entry.npc.name"
                                    class="book-npc-img"
                                    v-tip.npc="entry.npc"
                                />
                                <span v-else class="book-npc-text">{{ applyNpcFormat(entry.npcFormat, entry.npc) }}</span>
                            </div>

                            <div v-for="(itemsBlock, itemBlockIdx) in entry.itemBlocks" :key="`${entry.npc.id}-${itemBlockIdx}`" class="book-npc-block" :class="[separatorClass(itemsBlock.separator), alignClass(itemsBlock.align)]">
                                <template v-if="itemsBlock.items.length === 0">
                                    <span class="book-npc-missing">Brak przedmiotów</span>
                                </template>

                                <template v-else-if="itemsBlock.mode === 'img'">
                                    <img
                                        v-for="item in itemsBlock.items"
                                        :key="item.id"
                                        :src="item.src"
                                        :alt="item.name"
                                        class="book-npc-img"
                                        v-tip.item.top.show-id="item"
                                    />
                                </template>

                                <template v-else>
                                    <span v-for="(item, i) in itemsBlock.items" :key="item.id" class="book-npc-text">
                                        {{ applyItemFormat(itemsBlock.format, item) }}<span v-if="itemsBlock.separator === 'comma' && i < itemsBlock.items.length - 1">, </span><span v-else-if="itemsBlock.separator === 'space' && i < itemsBlock.items.length - 1"> </span>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="card">
            <h3 class="mb-3">Przykłady BBCode</h3>
            <Accordion>
                <AccordionPanel v-for="example in exampleSnippets" :key="example.title" :value="example.title">
                    <AccordionHeader>{{ example.title }}</AccordionHeader>
                    <AccordionContent>
                        <pre class="book-example-code">{{ example.code }}</pre>
                    </AccordionContent>
                </AccordionPanel>
            </Accordion>
        </div>
    </AppLayout>
</template>

<style scoped>
.book-preview {
    background: #f8f4e8;
    border: 1px solid #d5c8a5;
    border-radius: 8px;
    padding: 1rem;
    line-height: 1.45;
}

.book-preview-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    gap: 1rem;
}

.book-preview-pagination {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.book-preview-toggle {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    color: #6f6145;
}

.book-preview-page-title {
    font-weight: 700;
    color: #6f6145;
    margin-bottom: 0.5rem;
    padding-bottom: 0.4rem;
    border-bottom: 1px dashed #d5c8a5;
}

.book-npc-block.line .book-npc-text {
    display: block;
    margin-bottom: 0.2rem;
}

.book-npc-block.center {
    text-align: center;
}

.book-npc-block.center .book-npc-img {
    display: inline-block;
    margin-left: 0.2rem;
    margin-right: 0.2rem;
}

.book-npc-block.comma,
.book-npc-block.space {
    display: inline;
}

.book-npc-img {
    width: 40px;
    height: 40px;
    object-fit: contain;
    margin-right: 0.35rem;
    vertical-align: middle;
}

.book-npc-missing {
    color: #a52828;
}

.book-rich-entry {
    border-bottom: 1px dashed #d7c9a1;
    padding: 0.35rem 0 0.55rem;
    margin-bottom: 0.45rem;
}

.book-rich-entry:last-child {
    border-bottom: none;
}

.book-example-code {
    white-space: pre-wrap;
    margin: 0;
}
</style>
