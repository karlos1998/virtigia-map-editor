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

`replace_dialog` ma ten sam `data`, ale zamiast `key` wymaga `dialog_id`. Zastępuje cały graf dialogu, dlatego zawsze najpierw pokaż użytkownikowi podsumowanie.

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
