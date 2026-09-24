# BaseItemy

## Bezpieczny punkt wyjścia

Przed klonowaniem lub edycją wywołaj `get_base_item`. Odpowiedź zawiera pełne `attributes`, `attribute_points`, `manual_attribute_points`, kategorię, rzadkość, cenę, grafikę oraz obecne źródła. Nie odtwarzaj tych danych z pamięci ani na podstawie podobnej gry.

Nowy item od zera wymaga `image_data_uri` zawierającego PNG lub GIF dokładnie 32×32 px. Klon domyślnie zachowuje grafikę źródła; podaj `image_data_uri` tylko przy jej zmianie. Obraz z załącznika wolno zakodować do data URI wyłącznie w celu przekazania do MCP.

## Edycja atrybutów

- `attributes` zastępuje cały obiekt i nadaje się tylko do świadomej pełnej przebudowy.
- `attributes_patch` dodaje albo zmienia wskazane klucze bez naruszania pozostałych.
- Wartość `null` w `attributes_patch` usuwa klucz; jawna lista `remove_attributes` robi to samo i lepiej pokazuje zamiar w podsumowaniu.
- `attribute_points`, `manual_attribute_points` i `reverse_attributes` są niezależnymi obiektami. Nie przeliczaj ani nie usuwaj ich bez polecenia użytkownika.
- „20% lepszy” oznacza przemnożenie wskazanych liczbowych statystyk przez `1.2` i pokazanie dokładnych wartości przed/po. Ustal z treści, czy dotyczy to obrażeń, wszystkich statystyk bojowych czy konkretnego atrybutu; nie zwiększaj wymagań, opisu, identyfikatorów ani wartości logicznych.

## Sklep

Przed przypisaniem wywołaj `get_shop_inventory`. Sklep ma pozycje `0–79`, 8 kolumn i 10 rzędów:

`position = row × 8 + column`

`row` i `column` są numerowane od zera. Operacja `attach_item_to_shop` musi zawierać wolną `position` albo zgodną parę `row`/`column`. Serwer ponownie sprawdza zajętość przy zatwierdzaniu i stosowaniu commita.

## Loot i quest

- `attach_item_to_base_npc_loot` przypisuje item do istniejącego BaseNPC.
- W dialogowych regułach i akcjach użyj `@item:<key>`, gdy item jest tworzony lub klonowany w tym samym commicie. Serwer zamieni placeholder na prawdziwe ID.
- Zdobycz questa jest realizowana przez istniejące akcje dialogowe, np. `addItems`; wymaganie lub odebranie przedmiotu przez `items`/`removeItems`. Najpierw odczytaj sąsiedni dialog, żeby zachować prawidłowy kształt danych.
