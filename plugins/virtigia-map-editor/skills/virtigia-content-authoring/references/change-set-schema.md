# Schemat commitów AI

`draft_change_set` przyjmuje uporządkowaną tablicę `operations`. Operacje tworzące obiekty muszą wystąpić przed operacjami, które odwołują się do ich tymczasowych kluczy.

## Quest

```json
{
  "type": "create_quest",
  "key": "roan_missing_package",
  "data": {
    "name": "Zaginiona przesyłka Roana",
    "steps": [
      {"key": "ask_roan", "name": "Porozmawiaj z Roanem", "description": "Dowiedz się, co zaginęło."},
      {"key": "return_roan", "name": "Wróć do Roana", "description": "Przekaż Roanowi wieści."}
    ]
  }
}
```

W dialogach odwołuj się do utworzonych rekordów przez `@quest:roan_missing_package` oraz `@step:roan_missing_package:ask_roan`.

## Dialog

```json
{
  "type": "create_dialog",
  "key": "roan_package_dialog",
  "data": {
    "name": "Roan — zaginiona przesyłka",
    "nodes": [
      {
        "key": "start",
        "type": "start",
        "position": {"x": 0, "y": 0},
        "options": []
      },
      {
        "key": "question",
        "type": "special",
        "position": {"x": 250, "y": 0},
        "content": "Ej, wędrowcze. Moja przesyłka gdzieś przepadła.",
        "options": [
          {
            "key": "accept",
            "label": "Pomogę jej poszukać.",
            "additional_actions": {
              "setQuestStep": {"value": "@step:roan_missing_package:ask_roan"}
            }
          },
          {"key": "decline", "label": "Nie mam teraz czasu."}
        ]
      },
      {
        "key": "accepted",
        "type": "special",
        "position": {"x": 500, "y": 0},
        "content": "Wiedziałem, że można na ciebie liczyć.",
        "options": [{"key": "bye", "label": "Rozejrzę się."}]
      }
    ],
    "edges": [
      {"source_node_key": "start", "target_node_key": "question"},
      {"source_node_key": "question", "source_option_key": "accept", "target_node_key": "accepted"}
    ]
  }
}
```

Opcja bez krawędzi zamyka rozmowę. `additional_action` służy wyłącznie pojedynczym akcjom enum, np. `HEAL`; akcje questa zapisuj w `additional_actions`.

`replace_dialog` ma ten sam `data`, ale zamiast `key` wymaga `dialog_id`. Zastępuje cały graf dialogu i powinien być używany tylko wtedy, gdy użytkownik wyraźnie chce przebudować całość.

## Edycja istniejącego dialogu

Najpierw wywołaj `get_dialog_graph`, aby pobrać stabilne identyfikatory. Następnie użyj `patch_dialog`. Pola pominięte w patchu nie są zmieniane, więc istniejące sklepy, hotele i pozostałe gałęzie zostają zachowane. Po zastosowaniu commita serwer automatycznie rozkłada wszystkie węzły bez nakładania.

```json
{
  "type": "patch_dialog",
  "dialog_id": 123,
  "data": {
    "nodes": [
      {
        "id": 450,
        "options": [
          {
            "key": "ask_for_meat",
            "label": "Masz dla mnie jakieś zajęcie?",
            "rules": {"playerLevel": {"value": 10}}
          }
        ]
      },
      {
        "key": "meat_quest_offer",
        "type": "special",
        "content": "Przynieś mi pięć kawałków króliczego mięsa.",
        "options": [
          {"key": "accept", "label": "Zrobi się."},
          {"key": "decline", "label": "Może innym razem."}
        ]
      }
    ],
    "edges": [
      {
        "source_node_id": 450,
        "source_option_key": "ask_for_meat",
        "target_node_key": "meat_quest_offer"
      }
    ]
  }
}
```

Istniejące węzły, opcje i połączenia wskazuj przez `id`; nowe przez lokalny `key`. Nowe połączenia mogą używać `source_node_id`/`target_node_id` albo `source_node_key`/`target_node_key`. Dla nowej opcji w istniejącym węźle podaj `source_node_id` i jej `source_option_key`.

Usuwanie jest jawne przez `delete_node_ids`, `delete_option_ids` i `delete_edge_ids`. Aktualizacja istniejącego połączenia odbywa się przez wpis w `edges` zawierający jego `id`. Jeśli dialog jest współdzielony, zmiana dotyczy wszystkich korzystających z niego NPC — jest to zachowanie zamierzone.

## Przypisanie dialogu

```json
{
  "type": "assign_dialog_to_npc",
  "npc_id": 8,
  "dialog_key": "roan_package_dialog"
}
```

Można podać istniejące `dialog_id` zamiast `dialog_key`.

## Rozmieszczenie istniejącego BaseNPC

```json
{
  "type": "place_npc",
  "base_npc_id": 7,
  "enabled": true,
  "dialog_key": "roan_package_dialog",
  "locations": [
    {"map_id": 1, "x": 21, "y": 38}
  ]
}
```

Ta operacja tworzy wyłącznie instancję NPC. `base_npc_id` i każdy `map_id` muszą już istnieć.
