# Analiza walki Retro

Traktuj odpowiedzi Engine zwracane przez MCP jako źródło statystyk i ograniczeń. Nie odczytuj repozytorium Engine, jego konfiguracji, tokenów ani bazy danych.

## Wybór żywiołu

Odporność celu określa tylko obrażenia po redukcji. Nie wybieraj żywiołu wyłącznie dlatego, że cel ma na niego najniższą odporność.

- Trafienie zadające obrażenia od zimna nakłada na cel na dwie jego tury spowolnienie SA pochodzące z atakującego.
- `Zamrażający cios` daje atakom zimnem szansę odebrania przeciwnikowi następnej tury.
- `Lodowy pocisk` dodatkowo spowalnia pojedynczy cel, a `Lodowa zamieć` spowalnia wszystkich przeciwników.
- `Tarcza mrozu` zwiększa odporność na ogień. Dlatego przeciw przeciwnikowi atakującemu ogniem build zimna trzeba porównać nawet wtedy, gdy cel ma niższą odporność na ogień.
- Analogicznie oceniaj inne żywioły przez pełny zestaw umiejętności, efektów kontroli, tarcz i kosztów zasobów zwrócony przez narzędzia.

## Broń i druga ręka

- Broń dwuręczna i kostur wykluczają tarczę oraz broń pomocniczą.
- Broń jednoręczna pozwala użyć tarczy albo broni pomocniczej. Porównuj całe końcowe statystyki, a nie sam atak broni.
- Umiejętność może być rekomendowana tylko wtedy, gdy build spełnia każde `requiredArmaments` zwrócone w katalogu umiejętności.
- Nie zakładaj ogólnych reguł z innych gier. Uzasadniaj wybór konkretnymi statystykami przedmiotów i umiejętnościami zwróconymi przez MCP.

## Interpretacja symulacji

Powtarzaj pola `modeledMechanics`, `unmodeledMechanics` i `warnings` z wyniku. Model `normal_attack_monte_carlo_v1` nie wykonuje aktywnej rotacji, więc nie może wiarygodnie rozstrzygać między buildami, których przewaga wynika z aktywnych tarcz, leczenia, spowolnień lub debuffów. W takim przypadku wynik służy do porównania bazowych statystyk, a wniosek taktyczny musi uwzględniać mechaniki opisane przez Engine.

## Dostępność przedmiotów

Używaj filtrów `get_retro_build_options` zgodnie z poleceniem użytkownika. Wyjaśniaj źródło na podstawie zwróconych pól `sources`, `kind`, rangi i poziomu NPC, mapy, sklepu, ceny i waluty. Nie polecaj przedmiotu odfiltrowanego jako administracyjny, testowy, eventowy albo niedostępny.
