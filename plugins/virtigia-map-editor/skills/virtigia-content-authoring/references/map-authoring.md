# Mapy i kolizje

## Grafika i rozmiar

Nowa mapa wymaga grafiki dostarczonej przez użytkownika w `image_data_uri`:

- format PNG albo JPEG;
- szerokość i wysokość są podzielne przez 32 px;
- maksymalnie 4096 × 4096 px, czyli 128 × 128 pól;
- liczba pól mapy wynika z grafiki: `width_tiles = width_pixels / 32`, `height_tiles = height_pixels / 32`.

Nie twórz ścieżki ani nazwy pliku. Serwer nada unikalną nazwę, zapisze asset i utworzy miniaturę. Nie generuj ani nie zgaduj grafiki mapy. Jeśli prośba zależy od stylu, najpierw użyj `browse_visual_references`, ale nadal wymagaj finalnego załącznika od użytkownika.

## Format kolizji

Kolizja jest jednym stringiem zawierającym wyłącznie `0` i `1`:

- `0` — pole przechodnie;
- `1` — pole zablokowane;
- długość stringa musi wynosić dokładnie `width_tiles × height_tiles`;
- indeks pola `(x, y)` wynosi `y × width_tiles + x`;
- zapis przebiega wierszami: od lewej do prawej, następnie kolejny wiersz od góry do dołu;
- nie ma spacji, przecinków, nowych linii ani innych separatorów;
- pola poza granicami mapy silnik zawsze traktuje jako zablokowane.

Przykład mapy 4 × 3:

```text
0000
0110
0001
```

W bazie jest to dokładnie `000001100001`.

Przed zmianą istniejącej mapy wywołaj `inspect_map`. Narzędzie zwraca pełny string i tę samą kolizję rozbitą na wiersze. Do drobnej zmiany preferuj `update_map_collisions` z `mode: block` albo `mode: unblock` i listą `{x,y}`. `mode: replace` zastępuje cały string i wymaga szczególnej kontroli długości oraz kolejności.

`create_map` może otrzymać pełne `collision` albo prostszą listę `blocked_tiles`; gdy oba pola są pominięte, cała mapa zaczyna jako przechodnia.
