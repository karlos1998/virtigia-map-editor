<script setup lang="ts">
import { ref, computed } from 'vue';
import Dialog from 'primevue/dialog';
import Tree from 'primevue/tree';
import ProgressSpinner from 'primevue/progressspinner';
import Button from 'primevue/button';
import axios from 'axios';
import { route } from 'ziggy-js';

// Props
const props = defineProps<{
    visible: boolean;
}>();

// Emits
const emit = defineEmits<{
    'update:visible': [value: boolean];
    'select': [filePath: string];
}>();

// Local state
const treeNodes = ref<any[]>();
const isLoadingTree = ref(false);
const treeError = ref<string>('');

// Type for asset items
type AssetItem = {
    path: string;
    type: 'file' | 'dir';
};

// Computed visibility
const isVisible = computed({
    get: () => props.visible,
    set: (value: boolean) => emit('update:visible', value)
});

// Load tree nodes from API
const loadTreeNodes = async (path: string) => {
    try {
        console.log('Loading tree nodes for path:', 'img/outfits/' + path);
        const { data } = await axios.get<AssetItem[]>(route('assets.outfits.search', {
            path: 'img/outfits/' + path
        }));

        console.log('Received data:', data);

        return data.map((item) => {
            return {
                key: item.path,
                label: item.path,
                leaf: item.type === 'file',
                loading: false
            };
        });
    } catch (error) {
        console.error('Error loading tree nodes:', error);
        throw error;
    }
};

// Initialize tree - load outfits root directory
const initTree = async () => {
    try {
        isLoadingTree.value = true;
        treeError.value = '';
        console.log('Initializing outfits tree');
        const nodes = await loadTreeNodes('');
        console.log('Tree nodes loaded:', nodes);
        treeNodes.value = nodes;
    } catch (error: any) {
        console.error('Failed to initialize tree:', error);
        treeError.value = error.response?.data?.message || error.message || 'Nie udało się załadować drzewa katalogów';
    } finally {
        isLoadingTree.value = false;
    }
};

// Handle node expansion in tree
const onNodeExpand = (node: any) => {
    if (node.children) {
        return;
    }

    node.loading = true;

    setTimeout(async () => {
        try {
            const pathPart = node.key.split('img/outfits/')[1];
            console.log('Expanding node with path:', pathPart);
            const items = await loadTreeNodes(pathPart || '');
            node.children = [];
            items.forEach((item: any) => {
                node.children.push(item);
            });
        } catch (error) {
            console.error('Error expanding node:', error);
        } finally {
            node.loading = false;
        }
    }, 100);
};

// Handle dialog show
const onShow = () => {
    if (!treeNodes.value) {
        initTree();
    }
};

// Select outfit from tree
const selectOutfit = (filePath: string) => {
    // Extract just the filename from the full path
    const relativePath = filePath.split('img/outfits/')[1];
    console.log('Selected outfit:', relativePath);
    emit('select', relativePath || '');
    emit('update:visible', false);
};
</script>

<template>
    <Dialog
        v-model:visible="isVisible"
        header="Wybierz grafikę stroju"
        :modal="true"
        :style="{ width: '90vw', maxWidth: '1200px' }"
        :breakpoints="{ '960px': '95vw', '640px': '100vw' }"
        @show="onShow"
    >
        <!-- Loading state -->
        <div v-if="isLoadingTree" class="flex justify-center items-center p-8">
            <ProgressSpinner />
            <span class="ml-3 text-gray-600">Ładowanie katalogów...</span>
        </div>

        <!-- Error state -->
        <div v-else-if="treeError" class="p-4 bg-red-50 border border-red-200 rounded">
            <p class="text-red-700">
                <i class="pi pi-exclamation-triangle mr-2"></i>
                {{ treeError }}
            </p>
            <Button
                label="Spróbuj ponownie"
                @click="initTree"
                severity="secondary"
                class="mt-3"
            />
        </div>

        <!-- Tree view -->
        <Tree
            v-else-if="treeNodes && treeNodes.length > 0"
            :value="treeNodes"
            @node-expand="onNodeExpand"
            loadingMode="icon"
            class="outfit-tree w-full"
        >
            <template #default="{ node }">
                <!-- File node (leaf) - full width -->
                <div
                    v-if="node.leaf"
                    class="outfit-item w-full"
                >
                    <img
                        :src="node.label"
                        alt="Grafika"
                        class="outfit-img"
                    />
                    <a
                        :href="node.label"
                        target="_blank"
                        class="outfit-link min-w-96"
                    >
                        {{ node.label }}
                    </a>
                    <Button
                        label="Wybierz"
                        @click="selectOutfit(node.label)"
                        severity="success"
                        size="small"
                        class="outfit-btn"
                    />
                </div>

                <!-- Directory node -->
                <div v-else class="text-gray-700 text-lg font-medium py-2">
                    <i class="pi pi-folder mr-2 text-yellow-600"></i>
                    {{ node.label }}
                </div>
            </template>
        </Tree>

        <!-- Empty state -->
        <div v-else class="p-4 text-center text-gray-600">
            <i class="pi pi-folder-open text-4xl mb-2"></i>
            <p>Brak katalogów lub plików do wyświetlenia</p>
        </div>
    </Dialog>
</template>

<style scoped>
/* Force full width on all tree components */
.outfit-tree {
    width: 100%;
}

/* Target PrimeVue Tree internal structure for full width */
:deep(.p-tree) {
    width: 100%;
}

:deep(.p-tree-container) {
    width: 100%;
}

:deep(.p-tree-wrapper) {
    width: 100%;
}

/* Remove all default padding and margins from tree lists */
:deep(.p-tree ul) {
    width: 100%;
    padding: 0 !important;
    margin: 0 !important;
    list-style: none;
}

/* Add indentation only for nested lists */
:deep(.p-tree ul ul) {
    padding-left: 1.5rem !important;
}

/* Make all tree nodes full width */
:deep(.p-tree-node) {
    width: 100%;
    margin: 0;
}

/* Node content should be flex container */
:deep(.p-tree-node-content) {
    display: flex !important;
    align-items: center;
    width: 100%;
    gap: 0.5rem;
    padding: 0 !important;
}

/* Directory nodes need some padding for the toggle icon */
:deep(.p-tree-node:not(.p-tree-node-leaf) > .p-tree-node-content) {
    padding: 0.25rem 0 !important;
}

/* Toggle button styling */
:deep(.p-tree-toggler) {
    flex-shrink: 0;
    margin-right: 0.25rem;
}

/* For leaf nodes, ensure no padding interferes */
:deep(.p-tree-node-leaf > .p-tree-node-content) {
    padding: 0 !important;
}

/* The actual outfit item card - use negative margins to extend beyond any container padding */
.outfit-item {
    display: flex !important;
    align-items: center;
    gap: 1rem;
    width: 100% !important;
    margin: 0.5rem 0 !important;
    padding: 1rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    background: white;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    transition: all 0.2s;
}

.outfit-item:hover {
    background: #f9fafb;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.outfit-img {
    flex-shrink: 0;
    width: 128px;
    height: 128px;
    object-fit: contain;
    border-radius: 0.375rem;
    background: #f3f4f6;
    padding: 0.25rem;
}

.outfit-link {
    flex: 1;
    min-width: 0;
    color: #3b82f6;
    text-decoration: underline;
    font-size: 0.875rem;
    word-break: break-all;
}

.outfit-link:hover {
    color: #1d4ed8;
}

.outfit-btn {
    flex-shrink: 0;
}

/* Directory styling */
:deep(.p-tree-node:not(.p-tree-node-leaf) .text-gray-700) {
    padding: 0.25rem 0;
}
</style>
