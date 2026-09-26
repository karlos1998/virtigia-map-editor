# Dialogi Virtigii

Przed nietrywialnym tworzeniem lub edycją dialogu wywołaj `get_dialog_capabilities`. Przed edycją istniejącego grafu dodatkowo wywołaj `get_dialog_graph` i korzystaj z jego prawdziwych ID oraz `runtime_context`.

## Dwie warstwy reguł

- `options[].rules` decyduje, czy odpowiedź gracza jest dostępna.
- `edges[].rules` decyduje, czy można przejść konkretną krawędzią. Jedna odpowiedź może prowadzić do kilku celów o różnych warunkach.
- Opcja bez krawędzi kończy rozmowę.

Nie przenoś reguły na inną warstwę tylko dlatego, że obie mają taki sam kształt JSON.

## Typy węzłów

- `start` — niewidoczny początek grafu, ma bezpośrednie krawędzie.
- `special` — widoczna kwestia NPC. Może mieć tekst, fokus kamery, akcje wykonywane przy pokazaniu kwestii i co najmniej jedną opcję.
- `shop` — otwiera istniejący sklep wskazany przez `shop_id`.
- `hotel` — otwiera istniejący hotel wskazany przez `hotel_id`.
- `teleportation` — teleportuje na istniejącą mapę; może tworzyć instancję mapy wraz z NPC i skalowaniem poziomów.
- `randomizer` — wybiera bezpośrednią krawędź według `percentageChance`; sumuj szanse do 100.
- `profession` — rozdziela sześć profesji: `w`, `p`, `m`, `b`, `t`, `h`; w payloadzie MCP podaj dokładnie po jednej opcji (najlepiej z takimi samymi kluczami), a każda może mieć najwyżej jedną krawędź.
- `minigame` — `pipes`, `saper`, `mastermind` albo `random`, trudność `1–3`; wyjścia `source-success` i `source-fail`.

Pełne, bieżące pola każdego typu zwraca MCP. Nie wymyślaj dodatkowych pól.

## Fokus kamery

Fokus zapisuje się w `special.action_data.focus`.

```json
{"type":"npc","npcId":12,"locationId":34,"mapId":5,"x":18,"y":22}
```

Dla NPC skopiuj cały obiekt `focus` z `get_dialog_graph.runtime_context.focus_targets`. Lista zawiera tylko lokacje NPC z map, na których używany jest dialog.

```json
{"type":"coordinates","x":18,"y":22}
```

Współrzędne dotyczą kafelków bieżącej mapy. Klient centruje kamerę na środku kafelka.

```json
{"type":"reset"}
```

Fokus trwa przez kolejne kwestie, dopóki inny węzeł go nie zmieni, nie wykona `reset` albo rozmowa się nie zamknie. Zamknięcie dialogu zawsze resetuje kamerę.

## Tekst widoczny w kliencie

Obsługiwane placeholdery:

- `#nick` — nick postaci,
- `#lvl` — poziom postaci,
- `#difflvl(N)` — bezwzględna różnica między poziomem postaci i `N`.

Obsługiwany BBCode: `[b]`, `[i]`, `[u]`, `[href=URL]`, `[img]`, `[br]`. Surowy HTML jest usuwany.

Reguła `messageContent` na opcji otwiera w kliencie pole tekstowe. Jej `value` jest wymaganą odpowiedzią gracza i ma maksymalnie 100 znaków.

## Reguły

Każda reguła ma `value`, opcjonalne `value2` i opcjonalne `consume`. `consume: true` wolno stosować tylko dla `gold`, `honorPoints`, `items` i `dragonTears`.

- `gold`, `honorPoints`, `dragonTears` — wymagana wartość zasobu; może zostać zużyta.
- `level`, `levelBelow` — minimalny poziom albo poziom niższy niż próg.
- `brotherhood` — wymaga członkostwa w Karmazynowym Bractwie.
- `items` — `value` to lista ID itemów, `value2` to równoległa lista ilości.
- `equippedItems` — niepusta lista unikalnych ID; nie wolno podać dwóch kategorii tego samego slotu.
- `percentageChance` — `0–100`, przede wszystkim na krawędziach `randomizer`.
- `questStep` — `s-ID` oznacza dokładnie aktywny krok, `q-ID` rozpoczęty quest.
- `questBeforeStep` — dla `s-ID` wcześniejszy krok; dla `q-ID` quest nierozpoczęty.
- `questAfterStep` — używaj konkretnego `s-ID`; przechodzi na późniejszym kroku. Wariant całego questa ma nieintuicyjne zachowanie.
- `messageContent` — dokładna odpowiedź wpisana przez gracza.
- `dialogCounter` — `value` to ID licznika, `value2` ma postać `[">"|"="|"<", liczba]`.
- `seasonalEvent` — ID istniejącego wydarzenia.
- `timeAfter`, `timeBefore` — `HH:MM`.
- `weekday` — lista `1–7`, od poniedziałku do niedzieli.
- `activePlayersOnMap` — nieujemna liczba aktywnych graczy.
- `hasActiveBlessing` — `value: true`.

Istniejące liczniki, eventy, hotele i sklepy wyszukuj przez `search_game_content`.

## Akcje

`additional_actions` na węźle wykonuje się przy pokazaniu kwestii. Ten sam obiekt na opcji wykonuje się po kliknięciu odpowiedzi.

- `addItems`: `value` — lista ID lub `@item:key`; `value2` — równoległe ilości.
- `addGold`, `addHonorPoints`, `addExp` — wartość liczbowa.
- `addExpPercent` — `0–100`, najwyżej dwa miejsca po przecinku.
- `setQuestStep` — ID kroku albo `@step:quest-key:step-key`.
- `blessing` — ID istniejącego BaseItemu kategorii `blessings`; opcjonalne `scale`.
- `setOutfit` — wyłącznie istniejący asset i `duration` w minutach; `0` oznacza bezterminowo. AI nie tworzy outfitu.
- `addDialogCounter`, `resetDialogCounter` — ID istniejącego licznika.
- `resetAdditionalAttributePoints` — wartość liczbowa.

`additional_action` to osobna, pojedyncza akcja opcji: `HEAL`, `SELF_KILL`, `SUBTRACT_EXP`, `BATTLE`, `KILL_AND_LOOT`, `KILL`, `SHOW_MAIL`, `SHOW_DEPOSIT`, `SHOW_CLAN_DEPOSIT`, `SHOW_AUCTIONS`.

## Teleport instancyjny

`action_data.teleportation` zawsze wymaga `mapId`, `x`, `y`. Opcjonalne pola to `createInstance`, `includeNpcs`, `scaleNpcsToPlayerLevel`, `npcLevelOffset`, `scaleNpcLootItemLevels`, `npcLootItemLevelOffset`. Współrzędne muszą mieścić się w granicach mapy zwracanych przez wyszukiwanie/graf dialogu.

## Zakres AI

MCP może budować i patchować cały opisany graf, ale nie tworzy BaseNPC, map, grafik, hoteli/pokoi, liczników dialogowych, eventów sezonowych, drzwi, książek, audio, map tracków, spawnów ani special attacków. Może odwoływać się do istniejących rekordów udostępnionych przez narzędzia odczytu.
