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
5. Before changing an existing dialog, call `get_dialog_graph`. Use `patch_dialog`; a shared dialog is intentionally changed for every NPC that uses it and is not a reason to stop.
6. Build operations using [change-set-schema.md](references/change-set-schema.md), then call `draft_change_set`.
7. Show the user a compact summary of the validated draft. Call `apply_change_set` only after the user explicitly approves that draft.
8. Report the created or changed IDs returned by the server.

For rollback, call `list_change_sets`, identify the exact commit, and call `revert_change_set` only after confirmation. Rollback may be refused when later manual or AI edits touched the same records.

## Retro combat and loot analysis

For a question about defeating an NPC, equipment, skills, or drop probability:

1. Resolve the exact `base_npc_id` with `search_game_content`.
2. Call `inspect_retro_npc` for the real runtime profile after engine multipliers.
3. Call `get_retro_build_options` for each promising profession. Respect the returned skill-point budget, dependencies, required level, and required armament.
4. Propose a concrete build made only from returned item IDs and skill IDs, then call `simulate_retro_combat`. Iterate or compare professions when the chance is poor.
5. Explain that the returned chance is a Monte Carlo estimate and repeat the engine's reported model warnings.
6. For loot questions call `analyze_retro_loot`, passing the user's current kills-since-legendary and equipment bonuses when known.

These tools currently support only `retro`. They construct fake characters, items, and kill trackers in memory and never save them to MongoDB.

## Hard boundaries

- Never create items, BaseNPC definitions, maps, images, sprites, outfits, or other assets.
- `place_npc` may only instantiate an existing BaseNPC on an existing map.
- Quests and dialogs may reference only existing items found with `search_game_content`.
- Never bypass the draft-and-apply flow.
- Existing dialogue is a tone reference, not text to copy.
