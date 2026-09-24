---
name: virtigia-content-authoring
description: Create or edit Virtigia quests, dialogue graphs, BaseItems, shop and loot assignments, and placed NPCs, or analyze Retro combat and drops through the Virtigia Map Editor MCP. Use for world content, item graphics and attributes, builds, previewable AI commits, or rollback. Do not use it to create BaseNPC definitions or maps.
---

# Virtigia Content Authoring

Treat the Map Editor MCP as the source of truth. Never guess record IDs or game-world facts.

## Workflow

1. Call `profile` to confirm the employee identity and available worlds.
2. Use the world named by the user. If none is named, state that you are using `retro` before drafting changes.
3. Call `search_game_content` to resolve every referenced NPC, map, item, shop, quest, and dialog. If results are ambiguous, ask the user which exact result they mean.
4. Before writing player-facing dialogue, call `get_writing_context` and follow [content-style.md](references/content-style.md).
5. Before changing an existing dialog, call `get_dialog_graph`. Use `patch_dialog`; a shared dialog is intentionally changed for every NPC that uses it and is not a reason to stop.
6. Build operations using [change-set-schema.md](references/change-set-schema.md), then call `draft_change_set`.
7. Show the user a compact summary of the validated draft. Call `apply_change_set` only after the user explicitly approves that draft.
8. Report the created or changed IDs returned by the server.

For rollback, call `list_change_sets`, identify the exact commit, and call `revert_change_set` only after confirmation. Rollback may be refused when later manual or AI edits touched the same records.

## BaseItems, shops, loot and quest rewards

For BaseItem work, read [base-items.md](references/base-items.md).

1. Before cloning, scaling or editing, call `get_base_item` and use its exact current fields as the baseline.
2. A wholly new item needs an attached PNG/GIF image exactly 32×32. A clone may reuse the source image unless the user supplies a replacement.
3. Use `attributes_patch` and `remove_attributes` for targeted edits. When asked for a percentage improvement, calculate and show every exact before/after numeric value; preserve unrelated fields.
4. Before assigning to a shop, call `get_shop_inventory`. Select an explicit free position from `0` to `79`; there are 8 columns and 10 rows, and `position = row × 8 + column`.
5. Add shop and BaseNPC loot assignments in the same draft when requested. Reference a newly created item in dialogue rules/actions with `@item:<key>`.
6. Include the item, image, attribute diff, shop slot and loot/reward assignments in the approval summary.

## Retro combat and loot analysis

For a question about defeating an NPC, equipment, skills, or drop probability:

1. Read [retro-combat.md](references/retro-combat.md), then resolve the exact `base_npc_id` with `search_game_content`.
2. Call `inspect_retro_npc` for the real runtime profile after engine multipliers.
3. Call `get_retro_build_options` for each promising profession. Respect the returned skill-point budget, dependencies, required level, and required armament.
4. Use availability filters matching the request. Prefer items with returned, non-admin sources; never invent a source or recommend an item merely because it exists in the database.
5. Compare complete builds, including at least every plausible elemental path. Never choose an element from resistance alone: account for frost SA slowdown and freeze, elemental shields, active skills, legal weapon/off-hand combinations, resources, and the model limitations returned by the engine.
6. Propose a concrete build made only from returned item IDs and skill IDs, then call `simulate_retro_combat`. Iterate or compare professions when the chance is poor.
7. Explain that the returned chance is a Monte Carlo estimate and repeat the engine's reported model warnings. Never call a build "best" when an important advantage depends on an unmodeled mechanic.
8. For loot questions call `analyze_retro_loot`, passing the user's current kills-since-legendary and equipment bonuses when known.

These tools currently support only `retro`. They construct fake characters, items, and kill trackers in memory and never save them to MongoDB.

## Hard boundaries

- Never create BaseNPC definitions, maps, NPC sprites, outfits, or unrelated assets. BaseItem icons are allowed only through the validated 32×32 item-image field.
- `place_npc` may only instantiate an existing BaseNPC on an existing map.
- Quests and dialogs may reference existing items or BaseItems created earlier in the same commit through `@item:<key>`.
- Never bypass the draft-and-apply flow.
- Never call Retro Engine endpoints directly, read engine credentials or database configuration, inspect a local engine repository for game data, or create local scripts/clients as a fallback. If the required Map Editor MCP tool is missing or fails, stop and report that integration failure.
- Existing dialogue is a tone reference, not text to copy.
