# BaseNPC i grafiki NPC

BaseNPC jest definicją wyglądu i zachowania. `place_npc` tworzy dopiero instancję tej definicji na jednej lub wielu mapach.

## Grafika

Nowy BaseNPC wymaga dostarczonego `image_data_uri`:

- PNG albo GIF;
- maksymalnie 230 × 230 px;
- PNG jest statyczny;
- GIF jest odtwarzany jako cała animacja. Klient nie dzieli go na kierunkowy spritesheet.

Nie generuj i nie zgaduj grafiki. Przed oceną stylu użyj `browse_visual_references` dla kilku NPC o zbliżonej kategorii i randze.

## Renderowanie i kolizja

- `type: 0` — zwykły NPC/MOB: jest interaktywny i dynamicznie blokuje zajmowane pole;
- `type: 4` — warstwa dekoracyjna: nie jest interaktywna i nie blokuje pola;
- `facing` (`wt`) ustala kierunek początkowy: `0` południe, `1` północ, `2` zachód, `3` wschód;
- `draw_offset_x` i `draw_offset_y` przesuwają rysowanie względem kafelka; zakres bezpiecznego authoringu to -256..256.

Nie interpretuj `facing` jako wyboru klatki spritesheetu. Animowany wygląd zapewnia cały GIF.

## Pola gameplayowe

Wymagane są `name`, `level`, `rank` i `category`:

- `category`: `NPC` albo `MOB`;
- `rank`: `NORMAL`, `ELITE`, `ELITE_II`, `ELITE_III`, `HERO`, `TITAN`;
- `profession`: `w`, `p`, `m`, `b`, `t`, `h`;
- `is_aggressive` ma znaczenie dla `MOB`; domyślnie nowe MOB są agresywne, a NPC nie;
- `min_respawn_time` i `max_respawn_time` są nieujemne, a maksimum nie może być mniejsze od minimum.

W jednym commicie użyj `create_base_npc` z lokalnym `key`, a później `place_npc` z `base_npc_key`. Lokalizacja może wskazać istniejące `map_id` albo `map_key` utworzone wcześniej w tym samym commicie. Nowy BaseNPC może również otrzymać loot przez `attach_item_to_base_npc_loot` z `base_npc_key`.
