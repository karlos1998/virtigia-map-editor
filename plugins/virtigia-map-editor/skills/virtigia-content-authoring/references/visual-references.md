# Referencje wizualne Virtigii

## Co daje MCP

`browse_visual_references` zwraca metadane rekordów oraz rzeczywiste obrazy jako bloki graficzne MCP. Nie oceniaj stylu po samym `src` ani po nazwie pliku.

Obsługiwane rodzaje:

- `maps` — pełna grafika mapy zmniejszona proporcjonalnie maksymalnie do 1024 px na dłuższym boku; maksymalnie 6 obrazów w jednym wywołaniu;
- `items` — ikona powiększona do czytelnego podglądu metodą nearest-neighbour; maksymalnie 12 obrazów;
- `npcs` — pierwszy renderowalny obraz lub klatka powiększona metodą nearest-neighbour; maksymalnie 12 obrazów.

Można wybierać dokładne `ids`, szukać fragmentem `query` oraz filtrować itemy po `category` i `rarity`, a NPC po `category`, `rank`, `min_level` i `max_level`. Pole `image_index` łączy rekord metadanych z kolejnym blokiem obrazu.

## Jak dobierać próbkę

- Użyj co najmniej 4–6 trafnych przykładów, gdy użytkownik prosi o analizę stylu lub nową grafikę.
- Dla itemu najpierw wybierz tę samą kategorię, potem podobną rzadkość i poziom. Jeżeli próbka jest zbyt mała, dobierz sąsiednie rzadkości, ale zaznacz różnicę.
- Dla map dobierz lokacje o podobnej funkcji i klimacie: miasto do miasta, podziemia do podziemi, las do lasu. Jedna mapa nie definiuje całego stylu świata.
- Dla NPC oddzielaj postacie ludzkie, potwory i rangi specjalne. Nie wyciągaj zasad dotyczących zwykłego NPC z pojedynczego tytana lub herosa.

## Co porównywać

Opisuj wyłącznie cechy widoczne w pobranych obrazach:

- paletę, kontrast i nasycenie;
- skalę pikseli, ostrość krawędzi i użycie konturu;
- źródło światła, cienie i objętość;
- sylwetę oraz czytelność w docelowym rozmiarze;
- poziom detalu i wizualną gęstość;
- tło, przezroczystość i marginesy;
- powtarzalne motywy materiałów, ornamentów i efektów rzadkości.

Z kilku przykładów wyprowadź krótkie, jawne reguły dla bieżącego zadania. Nie kopiuj pojedynczego zasobu piksel w piksel i nie dopowiadaj niewidocznych cech.

## Granice

Oglądanie referencji działa w obrębie bieżącego zadania. Nie jest trwałym trenowaniem modelu i nie gwarantuje zapamiętania stylu w nowej rozmowie, dlatego przed każdym zadaniem wizualnym trzeba ponownie pobrać właściwą próbkę.

Narzędzie jest tylko do odczytu i niczego nie zapisuje w bazie ani storage. Dostęp do podglądów map i NPC nie rozszerza uprawnień autora: MCP nadal nie tworzy map, BaseNPC, sprite'ów NPC ani outfitów. Pozwala je analizować oraz lepiej dopasować dozwoloną grafikę itemu.
