---
name: virtigia-content-authoring
description: Create or edit Virtigia quests, dialogue graphs, and placed NPCs through the Virtigia Map Editor MCP. Use for game-content requests involving worlds such as Retro, NPC lookup, dialogue writing, previewable AI commits, or rollback. Do not use it to create items, BaseNPC definitions, maps, or graphics.
---

# Virtigia Content Authoring

Treat the Map Editor MCP as the source of truth. Never guess record IDs or game-world facts.

## Workflow

1. Call `profile` to confirm the employee identity and available worlds.
2. Use the world named by the user. If none is named, state that you are using `retro` before drafting changes.
3. Call `search_game_content` to resolve every referenced NPC, map, item, quest, and dialog. If results are ambiguous, ask the user which exact result they mean.
4. Before writing player-facing dialogue, call `get_writing_context` and follow [content-style.md](references/content-style.md).
5. Build operations using [change-set-schema.md](references/change-set-schema.md), then call `draft_change_set`.
6. Show the user a compact summary of the validated draft. Call `apply_change_set` only after the user explicitly approves that draft.
7. Report the created or changed IDs returned by the server.

For rollback, call `list_change_sets`, identify the exact commit, and call `revert_change_set` only after confirmation. Rollback may be refused when later manual or AI edits touched the same records.

## Hard boundaries

- Never create items, BaseNPC definitions, maps, images, sprites, outfits, or other assets.
- `place_npc` may only instantiate an existing BaseNPC on an existing map.
- Quests and dialogs may reference only existing items found with `search_game_content`.
- Never bypass the draft-and-apply flow.
- Existing dialogue is a tone reference, not text to copy.
