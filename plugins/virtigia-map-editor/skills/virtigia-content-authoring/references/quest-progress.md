# Mechanika progresu questów

Teksty `name` i `description` są wyłącznie informacją dla gracza. Silnik nie odczytuje z nich celu zadania. Jeżeli krok wymaga zabicia mobów, upływu czasu albo następnego dnia, zapisz to w odpowiednim polu mechaniki.

## Zabijanie mobów

Użyj `auto_progress.type = "mobs"`. Każdy cel wskazuje istniejący `base_npc_id` albo `mob_species_id` i dodatnią `quantity`.

```json
{
  "key": "kill_selder_group",
  "name": "Pokonaj Seldera i mnichów",
  "description": "Pokonaj wskazanych przeciwników.",
  "auto_progress": {
    "type": "mobs",
    "mobs": [
      {"type": "base_npc", "base_npc_id": 1189, "quantity": 10},
      {"type": "base_npc", "base_npc_id": 1188, "quantity": 15},
      {"type": "base_npc", "base_npc_id": 1190, "quantity": 1}
    ]
  }
}
```

- `type: "base_npc"` wymaga wyłącznie `base_npc_id`.
- `type: "mob_species"` wymaga wyłącznie `mob_species_id`.
- Wszystkie cele muszą zostać wykonane. Dopiero wtedy silnik automatycznie aktywuje następny krok według kolejności ID kroków.
- Rozwiąż każde ID przez `search_game_content`; nie zgaduj go z nazwy.

## Upływ czasu

```json
{
  "key": "wait_for_repair",
  "name": "Poczekaj na naprawę",
  "description": "Wróć po godzinie.",
  "auto_progress": {
    "type": "time",
    "time_seconds": 3600
  }
}
```

Po `time_seconds` silnik aktywuje następny krok według kolejności ID. Krok czasowy nie może mieć listy `mobs`.

## Następny dzień

Dla questa tworzonego w tym samym commicie użyj `auto_advance_next_day: true` i opcjonalnego `auto_advance_to_step_key`. Przy `patch_quest` odpowiednikiem jest `auto_advance_to_step_id`.

Nie łącz `auto_progress` z `auto_advance_next_day` w jednym kroku. Jeżeli cel następnego dnia jest `null`, silnik czyści postęp questa zamiast aktywować inny krok.

## Obowiązkowa kontrola

1. Przed naprawą istniejącego questa wywołaj `get_quest`.
2. Użyj `patch_quest`, wskazując stabilne `step_id`.
3. Pokaż w podsumowaniu draftu każdy cel, jego ID i ilość oraz krok, który ma nastąpić później.
4. Po `apply_change_set` ponownie wywołaj `get_quest` i porównaj zapis z żądaniem.

Serwer odrzuca nieznane pola zamiast je ignorować. Pola takie jak `kill_targets` i `on_complete_step_key` nie należą do schematu i nie mogą być używane.
