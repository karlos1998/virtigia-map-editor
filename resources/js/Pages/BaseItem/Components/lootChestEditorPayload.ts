import type { BaseItemResource } from "@/Resources/BaseItem.resource";

export const extractBaseItemId = (value: unknown): number | null => {
    if (value === null || value === undefined) {
        return null;
    }

    if (Array.isArray(value)) {
        for (const item of value) {
            const baseItemId = extractBaseItemId(item);

            if (baseItemId !== null) {
                return baseItemId;
            }
        }

        return null;
    }

    if (typeof value === "object") {
        if ("id" in value) {
            return extractBaseItemId((value as { id?: unknown }).id);
        }

        if ("baseItemId" in value) {
            return extractBaseItemId((value as { baseItemId?: unknown }).baseItemId);
        }

        if ("value" in value) {
            return extractBaseItemId((value as { value?: unknown }).value);
        }
    }

    const parsed = Number(value);

    if (!Number.isInteger(parsed) || parsed <= 0) {
        return null;
    }

    return parsed;
};

export const extractBaseItemResource = (value: unknown): BaseItemResource | null => {
    if (value === null || value === undefined) {
        return null;
    }

    if (Array.isArray(value)) {
        for (const item of value) {
            const baseItem = extractBaseItemResource(item);

            if (baseItem) {
                return baseItem;
            }
        }

        return null;
    }

    if (typeof value !== "object") {
        return null;
    }

    if ("value" in value) {
        const baseItem = extractBaseItemResource((value as { value?: unknown }).value);

        if (baseItem) {
            return baseItem;
        }
    }

    const baseItemId = extractBaseItemId(value);

    if (baseItemId === null) {
        return null;
    }

    if ("id" in value && (value as { id?: unknown }).id === baseItemId) {
        return value as BaseItemResource;
    }

    return {
        ...(value as BaseItemResource),
        id: baseItemId,
    };
};

export const resolveRewardBaseItemId = (reward: {
    baseItemId?: unknown;
    resolvedItem?: unknown;
}): number | null => extractBaseItemId(reward.baseItemId) ?? extractBaseItemId(reward.resolvedItem);
