# Przejścia między mapami

Przejście jest skierowanym rekordem. Zapis `A → B` nie tworzy automatycznie `B → A`. Pełne przejście dwukierunkowe składa się z dwóch rekordów, a każdy kierunek może mieć inne ograniczenia.

## Obowiązkowe rozpoznanie

1. Znajdź dokładne mapy przez `search_game_content`.
2. Wywołaj `inspect_map_transitions` dla każdej mapy, której dotyczy zmiana.
3. Używaj wyłącznie zwróconych identyfikatorów, wymiarów i współrzędnych. Współrzędne kafelków są liczone od zera.
4. Sprawdź zarówno `outgoing`, jak i `incoming`. `paired_transition_id` oznacza dokładną parę ze zgodnymi polami wejścia i lądowania; sama obecność rekordu pomiędzy tymi mapami nie oznacza poprawnego powrotu.

Każdy rekord ma:

- pole źródłowe `source.map_id`, `source.x`, `source.y`;
- pole lądowania `destination.map_id`, `destination.x`, `destination.y`;
- inkluzywne `requirements.min_level` i `requirements.max_level`; `null` oznacza brak granicy;
- opcjonalny `requirements.required_base_item`; przedmiot musi być w torbie, ale przejście go nie zużywa;
- opcjonalny `hotel_room`; wtedy silnik sprawdza ważny klucz konkretnego pokoju zamiast zwykłego `required_base_item`.

Silnik pozwala użyć przejścia z jego kafelka lub kafelka sąsiedniego, gdy bezwzględna różnica obu współrzędnych nie przekracza 1. Routing questów traktuje rekordy jako skierowane krawędzie i pomija kierunki zablokowane przez poziom, przedmiot albo klucz hotelowy.

## Bezpieczna edycja

- `create_map_transition` tworzy dokładnie jeden kierunek. Dla przejścia dwukierunkowego dodaj dwie operacje i ustaw lądowanie każdego kierunku na kafelek drugiego.
- `update_map_transition` zmienia tylko podane pola. Jeśli przesuwasz lub przekierowujesz jeden koniec pary, jawnie popraw również drugi rekord; serwer nie zgaduje zamiaru.
- `delete_map_transition` usuwa jeden kierunek. Nie usuwaj automatycznie rekordu powrotnego.
- Przejścia przypisanego do pokoju hotelowego nie można usunąć przez commit AI.
- Na polu źródłowym nie może już stać NPC ani inne przejście. Pole źródłowe i docelowe muszą mieścić się w wymiarach swoich map.
- Przed zastosowaniem pokaż oba kierunki, mapy, współrzędne, poziomy i wymagany przedmiot. Po zastosowaniu ponownie wywołaj `inspect_map_transitions` dla obu map i sprawdź dokładną parę.

Nowy BaseItem utworzony wcześniej w tym samym commicie można ustawić jako wymaganie przez `required_base_item_key`. Istniejący przedmiot wskazuje się przez `required_base_item_id`. Ustawienie `required_base_item_id` na `null` usuwa zwykłe wymaganie przedmiotu.
