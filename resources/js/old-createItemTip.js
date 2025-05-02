function isset(a) {
    return "undefined" != typeof a;
}

function htmlspecialchars(a) {
    return (
        (a = a.replace(/&/g, "&amp;")),
            (a = a.replace(/"/g, "&quot;")),
            (a = a.replace(/'/g, "&#039;"))
    );
}

function mp(a) {
    return a > 0 ? "+" + a : a;
}

function ut_date(a) {
    const b = new Date(1e3 * a),
        c = b.getYear();

    return b.getDate() + "/" + (b.getMonth() + 1) + "/" + (c > 99 ? c - 100 : c);
}

function parseItemStat(a) {
    a = a.split(";");
    for (var b = {}, c = 0; c < a.length; c++) {
        const d = a[c].split("=");
        b[d[0]] = isset(d[1]) ? d[1] : null;
    }
    return b;
}

const __translations = {
    advent: {
        advent_close: "Zamknij kalendarz",
        advent_header: "Kalendarz adwentowy",
        open_window: "Otwórz okienko",
    },
    auction: {
        at_least: "Co najmniej",
        "auction_cost %cost%":
            "Koszt wystawienia przedmiotu wynosi %cost% złota, a po jego sprzedaży pobierane jest 10% podatku. Zamiast pisać 1000 możesz napisać 1k (tysiąc) lub analogicznie: 1m (milion), 1g (miliard).",
        auction_ended: "Aukcja<br>zakończona",
        au_auction_list: "Lista aukcji",
        au_buynow: "Kup teraz: ",
        au_buynow_price: "Cena kup teraz: ",
        au_cat1: "Broń biała jednoręczna",
        au_cat10: "Buty",
        au_cat11: "Rękawice",
        au_cat12: "Pierścienie",
        au_cat13: "Naszyjniki",
        au_cat14: "Tarcze",
        au_cat15: "Neutralne",
        au_cat16: "Konsumpcyjne",
        au_cat2: "Broń biała dwuręczna",
        au_cat21: "Strzały",
        au_cat22: "Talizmany",
        au_cat23: "Książki",
        au_cat3: "Broń biała półtoraręczna",
        au_cat4: "Broń dystansowa",
        au_cat5: "Broń pomocnicza",
        au_cat6: "Różdżki magiczne",
        au_cat7: "Laski magiczne",
        au_cat8: "Zbroje",
        au_cat9: "Hełmy",
        au_cat99: "Eventowe",
        au_limit_nfo: "Pamiętaj, że twój limit złota wynosi ",
        au_price_info_txt:
            "Cena przedmiotu to 10SŁ + 1/10 poziomu przedmiotu, +20% za unikat, +50% za heroik, +200% za legendarny. Sprzedający otrzymuje 10% tej sumy, reszta to koszty odwiązania.",
        au_price_info_txt_gold:
            "* Dodatkowo, sprzedający otrzyma 95% zapłaconego złota. Prowizja za wystawienia takiej aukcji wynosi 1m złota.",
        au_sl: "SŁ. ",
        au_time: "Czas: ",
        au_yours: "Twoje aukcje:",
        au_yours_nfo: "Aby dodać nową aukcje kliknij przedmiot w ekwipunku.",
        endat_th: "Koniec",
        filter_name: "Nazwa",
        filter_prof: "Prof.",
        "item_confirm_au %name% %val%":
            "<center>Czy na pewno chcesz licytować przedmiot<br><b>%name%</b><br>za %price% złota?</center>",
        "item_confirm_buynow %name%":
            "<center>Czy na pewno chcesz kupić przedmiot<br><b>%name%</b> ?</center>",
        item_th: "Item",
        "minimal_offer %val%": "Minimalna oferta:<br>%val%",
        mybs_price_th: "Cena",
        name_th: "Nazwa",
        noauction_block: "Tego przedmiotu nie można wystawić na aukcję.",
        offer_th: "Oferta",
        price_buynow_th: "Cena kup teraz",
        price_filter: "Cena",
        price_th: "Cena",
        prof_all: "wszystkie",
        prof_bdancer: "tancerz ostrzy",
        prof_hunter: "łowca",
        prof_mage: "mag",
        prof_paladin: "paladyn",
        prof_tracker: "tropiciel",
        prof_warrior: "wojownik",
        soulbound_sell_info:
            "UWAGA! Wystawiasz na aukcję przedmiot związany, jeśli nie zostanie sprzedany automatycznie ulegnie zniszczeniu. Czy na pewno chcesz wystawić ten przedmiot na aukcję ?",
        type_heroic: "heroiczne",
        type_legendary: "legendarne",
        type_normal: "zwykłe",
        type_unique: "unikaty",
    },
    battle: {
        "ans_game msg": "Broń się! Wybierz szybko dwa takie same symbole.",
        attack: "Atakuj",
        auto_btn_txt: "Kończy walkę automatycznie (skrót - F)",
        battle_ended: "Walka zakończona.",
        battle_no_winner: "Walka nie wyłoniła zwycięzcy.",
        "energy_amount %val%": "Energia: %val%",
        "life_percent %val%": "Życie: %val%%",
        "mana_amount %val%": "Mana: %val%",
        move_forward: "Krok do przodu",
        someoneelse_move: "Ruch kogoś innego.",
        "winner_is %name% %posfix%": "Zwyciężył%posfix% %name%.",
        "winner_team_is %name% %posfix%": "Zwyciężyła drużyna %name%.",
        "your_move %sec%": "Twój ruch, pozostało %sec%s",
    },
    bm: {
        add_normal_attack_tip: "Dodaje zwykły atak do listy umiejejętności",
        auto_skills_list:
            "Lista automatycznie wykonywanych umiejętności podczas szybkiej walki:",
        hide_editor_panel: "Schowaj panel edytora",
        loop_skills: "Powtarzanie umiejętności po wykonaniu ostatniej z listy",
        remove_all_fromlist: "Usuwa wszystkie elementy z listy",
    },
    buff: {
        critical_deep_wound: "Krytyczna głęboka rana",
        deep_wound: "Głęboka rana",
        fire: "Ogień",
        poisoned: "Zatrucie",
        speed_up: "Przyspieszenie",
        swow_down: "Spowolnienie",
        wound: "Zranienie",
    },
    buttons: {
        addons_tip: "Dodatki do gry",
        chat_button_nfo:
            "<strong>Czat - pomoc</strong><br />Kliknięcie na nick - wiadomość prywatna.<br />Ctrl+kliknięcie na nick - wklejenie nicka w pole wiadomości.<br /><br /><strong>Przydatne komendy:</strong><br />/r - odpowiedź na wiadomość prywatną<br />/g - wiadomość dla drużyny<br />/k - wiadomość na czacie klanowym<br />/lvl nick - sprawdzenie poziomu i profesji gracza",
        clans_tip: "Lista klanów",
        config_tip: "Konfiguracja",
        friends_tip: "Lista przyjaciół i wrogów",
        map_tip: "Minimapa świata gry",
        pvp_tip: "Miecze: zgoda na PvP<br>Tarcza: brak zgody",
        quests_tip: "Aktywne questy",
        skillset_chng: "Kliknij aby zmienić zestaw umiejętności",
        skills_tip: "Lista umiejętności",
    },
    chat: {
        dont_repeat_msg: "Nie powtarzaj wiadomości.",
        dont_write_so_fast: "Nie pisz tak szybko.",
        "[G]": "[G] ",
        "[K]": "[K] ",
        "[P]": "[P]",
    },
    clan: {
        addClan: "Dodaj",
        add_new_rank: "Dodaj nową rangę",
        cancel: "Anuluj",
        cancel_alignment: "Na pewno chcesz anulować ten sojusz?",
        cancel_war: "Na pewno chcesz anulować tę wojnę?",
        "clan_diplomacy_header %name%": "Dyplomacja klanu %name%",
        clan_edit_mainpage: "Edytuj oficjalną stronę",
        clan_edit_privpage: "Edytuj prywatną stronę",
        clan_e_info: "Edycja rangi<br>i wyrzucanie",
        clan_invite_new: "Zaproś do klanu",
        clan_leave: "Opuść klan",
        clan_losts: "Przegr.",
        clan_manage_header: "Zarządzanie klanem",
        clan_name_change: "Zmien nazwę klanu (15SŁ):",
        clan_ops_histry: "Historia operacji klanowych",
        clan_power: "Moc",
        "clan_rank %lvl% %exp%": "Ranga klanu: %lvl%/5 (+%exp%% exp)",
        clan_resolve: "Rozwiąż klan",
        clan_sl: "SŁ klanowe",
        clan_wins: "Wygr.",
        click_to_upg: "Kliknij aby ulepszyć klan.",
        cl_del: "Usuń",
        cl_name_save: "Zmień",
        confirm_rank_delete: "Czy na pewno chcesz usunąć tę rangę?",
        creator_th: "Założyciel",
        "credits_in %amount%":
            "Czy jesteś pewny, że chcesz wpłacić <b>%amount% SŁ do skarbca?</b> Ze skarbca nie można wypłacać SŁ, więc jeśli nie zostaną wydane, przepadną po rozwiązaniu klanu.",
        diplom_th: "Dyplomata",
        disband_gold_info:
            "Skarbiec klanowy nie jest pusty,<br>rozwiązując klan stracisz jego zawartość.",
        dismiss_member: "Wyrzuć",
        dismiss_th: "Usuwanie z klanu",
        "edit_rank %name%": "Edycja rangi %name%",
        enemy_clans: "Wrogie klany",
        friend_clans: "Przyjazne klany",
        give_clan_leadership: "Przekaż przywództwo klanu (10SŁ)",
        "give_clan_leadership_confirm %name%":
            'Oddaj przywództwo klanowe graczowi "%name%". Koszt 10SŁ',
        "give_gold %amount%": "Wypłać",
        "gold_amount %amount%": "Złoto: %amount%",
        gold_cl: "Złoto",
        "gold_deposit_confirm %amount%":
            "Czy na pewno chcesz wpłacić <b>%amount%</b> złota do skarbca ?",
        gold_th: "Skarbnik",
        "gold_widthdraw %amount% %player%":
            "Czy na pewno chcesz wypłacić <b>%amount%</b> złota graczowi <b>%player%</b>",
        hist_filter_depo: "Z depozytem",
        hist_filter_nodepo: "Bez depozytu",
        inviteClan: "Zaproś",
        invit_th: "Zapraszanie do klanu",
        logo_save: "Zapisz",
        logo_url: "Logo (pełny adres http):",
        mainedit_th: "Edycja oficjalnej strony",
        main_page: "Strona oficjalna",
        new_alignment: "Nowy sojusz:",
        new_enemy: "Nowy wróg",
        "offline_from %time%": "offline <br /> od %time%",
        ok_to_confirm: "Wpisz OK:",
        outfit_available: "Strój klanowy dostępny. ",
        outfit_notavailable: "Strój klanowy nie jest dostępny.",
        outUse_th: "Używanie klanowego stroju",
        player_dismiss_confirm: "Na pewno chcesz wyrzucić gracza %name%?",
        "player_edit %name%": "Edycja gracza %name%",
        plrs_amount: "Graczy",
        private_page: "Strona prywatna",
        private_sl: "SŁ prywatne",
        privedit_th: "Edycja prywatnej strony",
        privPage_cancel: "Anuluj",
        privPage_save: "Zapisz",
        put_gold: "Wpłać",
        put_sl: "Wpłać SŁ:",
        put_sl_in: "Wpłać",
        put_sl_off: "Wyłącz wpłacanie SŁ",
        put_sl_on: "Włącz wpłacanie SŁ",
        rank: "Ranga:",
        ranks_info_txt:
            "Uwaga: można edytować oraz nadawać rangi tylko poniżej swojej.",
        rank_cancel: "Anuluj",
        rank_del: "Usuń",
        rank_diplomate: "Dyplomata",
        rank_dismiss: "Wyrzucanie",
        rank_inviting: "Zapraszanie",
        rank_name: "Nazwa rangi:",
        rank_officialpage: "Strona oficjalna",
        rank_outfit: "Strój klanowy",
        rank_privatepage: "Strona prywatna",
        rank_rEdit: "Nadawanie rang",
        rank_save: "Zapisz",
        rank_tabs_itemlimit: "Limit depozytu[?]:",
        rank_tabs_itemlimit_tip:
            "Limit przedmiotów na dzień możliwych do wyjęcia z depozytu (0-3, 0-brak limitu)",
        rank_tabs_usage: "Używanie depozytu[?]:",
        rank_tabs_usage_tip: "Dostępne zakładki (0-5, 0-nie można uzywać)",
        rank_tabs_view: "Przeglądanie depozytu[?]:",
        rank_tabs_view_info:
            "Ilość widocznych zakładek nie może być mniejsza niż ilość zakładek, których można używać.",
        rank_tabs_view_tip:
            "Dostepne zakładki (0-5, 0-nie można korzystać z depozytu)",
        rank_th: "Ranga",
        rank_treasury: "Skarbnik",
        redit_td: "Edytuj",
        rEdit_th: "Nadawanie rang",
        save: "Zapisz",
        show_clan_upg_opts: "Pokaż opcje ulepszenia klanu",
        sl: "SŁ",
        "slamount %amount%": "Smocze łuski: %amount%",
        tablimit_th: "Limit wyjmowanych przedmiotów",
        "tabs_view no_access": "- brak dostępu",
        "tabs_view no_limit": "- bez limitu",
        "tabs_view no_usage": "- nie można używać",
        tabuse_th: "Używanie zakładek klanowego depozytu",
        tabview_th: "Przeglądanie zakładek klanowego depozytu",
        th_lp: "Lp.",
        th_nick: "Nick",
        th_rank: "Ranga",
        th_status: "Status",
        treasury_manage: "Skarbiec",
        upg_confirm: "Potwierdzam!",
        use_outfit: "Ubierz się",
    },
    clan_right_short: {
        depo_tabs_access: "Du",
        depo_tabs_view: "Dt",
        depo_tabs_withdraw: "Di",
        diplomat: "D",
        founder: "Z",
        g_expel: "W",
        g_invite: "Za",
        g_outfit: "St",
        official_edit: "O",
        private_edit: "P",
        rank_edit: "N",
        treasury: "S",
    },
    codeManager: {
        give_promo_code: "Podaj kod promocyjny",
    },
    critval: {
        cold: "zimna",
        fire: "ognia",
        lightning: "błyskawic",
    },
    default: {
        "+third_strike": "+Trzeci cios",
        achievement_list: "Lista osiągnięć:",
        ach_btn: "OSIĄGNIĘCIA",
        active_fewdays_ago: "był(a) kilka dni temu",
        active_sometime_ago: "niedawno aktywny(a)",
        add_gold_sl_au: "Wymagaj dopłaty w złocie",
        add_gold_sl_au_info:
            "Dla przedmiotów heroicznych i legendarnych możliwość dopłaty w złocie.",
        "ammo %val% %split%": "Strzał: %val% %split%",
        "amount %val% %split%": "Ilość: %val% %split%",
        auction_item_description: "Wywołanie aukcji",
        au_offer_no: "brak",
        au_offer_yes: "jest",
        "bandays_amount for %name%": "Na ile dni chcesz dać bana graczowi %name% ?",
        banday_reason_label: "Powód bana (min 10 znaków):",
        banday_wrong_values_error: "Wybierz poprawną wartość i podaj powód bana.",
        "battle_starts_between %grp1% %grp2%":
            "Rozpoczęła się walka pomiędzy %grp1% a %grp2%",
        binds1: "Przeciągnij na postać, by aktywować",
        binds2: "Wiąże po założeniu",
        "bonus_abdest %val%": "Niszczenie %val% absorpcji przed atakiem",
        "bonus_absorb %val%": "Absorbuje do %val% obrażeń fizycznych",
        "bonus_absorbd %val%":
            "Absorpcja o %val%% skuteczniejsza przeciw broni dystansowej",
        "bonus_absorbm %val%": "Absorbuje do %val% obrażeń magicznych",
        "bonus_acdmg %val%": "Niszczy %val% punktów pancerza podczas ciosu",
        "bonus_adest %val%": "Zadaje %val% obrażeń właścicielowi",
        "bonus_afterheal2 %val%": "% szans na wyleczenie %val% obrażeń po walce",
        "bonus_allslow %val%": "Spowolnienie wrogów o %val%% SA",
        "bonus_arrowblock %val%":
            "Podczas obrony szansa na zablokowanie strzały/bełtu %val%%",
        "bonus_aura-ac %val%": "Aura: pancerz %val%",
        "bonus_aura-resall %val%": "Aura: odporności magiczne %val%%",
        "bonus_aura-sa %val%": "Aura: SA %val%",
        "bonus_bag %val%": "Mieści %val% przedmiot%posfix%",
        "bonus_blizzard %val%": "%val% obrażeń od zamieci",
        "bonus_blok %val%": "Blok %val%",
        "bonus_btype %val%": "Tylko %val%",
        "bonus_contra %val%": "+%val%% szans na kontrę po krytyku",
        "bonus_cover %val%": "Przejęcie na siebie %val%% obrażeń",
        bonus_creditsbon: "Dodaje jedną Smoczą Łzę",
        "bonus_crit %val%": "Cios krytyczny +%val%%",
        "bonus_critmval_c %val%": "Siła krytycznego uderzenia magii zimna +%val%%",
        "bonus_critmval_f %val%": "Siła krytycznego uderzenia magii ognia +%val%%",
        "bonus_critmval_l %val%":
            "Siła krytycznego uderzenia magii błyskawic +%val%%",
        "bonus_critval %val%": "Siła krytyka fizycznego +%val%%",
        "bonus_da %val%": "Wszystkie cechy %val%",
        "bonus_di %val%": "Intelekt %val%",
        "bonus_ds %val%": "Siła %val%",
        "bonus_dz %val%": "Zręczność %val%",
        "bonus_en-regen %val%": "Odzyskanie 5x%val% energii",
        "bonus_endest %val%": "Niszczenie %val% energii podczas ataku",
        "bonus_energy1 %val%": "Koszt energii: %val%",
        "bonus_energy2 %val%": "Zysk energii: %val%",
        "bonus_energybon %val%": "Energia %val%",
        "bonus_energygain %val%": "Energia %val% co turę",
        "bonus_energyp1 %val%": "Energia %val%%",
        "bonus_energyp2 %val%": "Zysk energii: %val%%",
        "bonus_evade %val%": "Unik %val%",
        "bonus_fire %val%": "Obrażenia od ognia ~%val%",
        "bonus_firebolt %val%": "%val% dodatkowych obrażeń od ognia",
        "bonus_firewall %val%": "Ściana ognia %val% obrażeń",
        "bonus_freeze %val%": "Szansa na zamrożenie %val%%",
        "bonus_frost %val% %slow%":
            "Obrażenia od zimna +%val%<br>oraz spowalnia cel o %slow% SA",
        "bonus_fullheal %val%":
            "Pełne leczenie, pozostało %val% punktów uleczania.",
        "bonus_gold %val%": "Złoto %val%",
        "bonus_goldpack %val%": "Zawiera %val% złota",
        "bonus_heal %val%": "Przywraca %val% punktów życia podczas walki",
        "bonus_heal1 %val%": "Moc leczenia +%val%",
        "bonus_healall %val%": "Przywrócenie wszystkim %val% punktów życia",
        "bonus_hp %val%": "Życie %val%",
        "bonus_hpbon %val%": "+%val% życia za 1 pkt siły",
        "bonus_hpcost %val%": "Strata %val%% życia",
        "bonus_leczy %val%": "Leczy %val% punktów życia",
        "bonus_light %val%": "Obrażenia od błyskawic 1-%val%",
        "bonus_lowcrit %val%":
            "Podczas obrony szansa na cios krytyczny przeciwnika jest mniejsza o %val%%",
        "bonus_lowdmg %val%": "Następny atak wroga obniżony o %val%%",
        "bonus_lowevade %val%":
            "Podczas ataku unik przeciwnika jest mniejszy o %val%",
        "bonus_mana1 %val%": "Koszt many: %val%",
        "bonus_mana2 %val%": "Zysk many: %val%",
        "bonus_manabon %val%": "Mana %val%",
        "bonus_manadest %val%": "Niszczenie %val% many podczas ataku",
        "bonus_managain %val%": "Mana %val% co turę",
        "bonus_manarestore %val%": "Przywrócenie %val% many",
        "bonus_manastr %val%": "Mana +%val%*intelekt",
        "bonus_manatransfer %val%": "Transfer %val% many",
        "bonus_of-crit %val%": "Cios krytyczny pomocniczy +%val%%",
        "bonus_of-critmval %val%": "Siła krytyka magicznego +%val%%",
        "bonus_of-critval %val%": "Siła krytyka broni pomocniczej +%val%%",
        "bonus_parry %val%": "+%val%% szans na sparowanie ataku",
        "bonus_perheal %val%": "Leczy %val%% życia",
        "bonus_pierce %val%": "Przebicie pancerza +%val%%",
        "bonus_pierceb %val%": "%val%% szans na zablokowanie przebicia",
        bonus_pkey: "Klucz główny",
        "bonus_poison %val% %slow%":
            "Obrażenia od trucizny +%val%<br>oraz spowalnia cel o %slow% SA",
        "bonus_resdmg %val%": "Obniżenie odporności o %val%% podczas ciosu",
        "bonus_respred %val%": "Przyśpiesza wracanie do siebie o %val%%",
        "bonus_runes %val%": "Dodaje %val% Smoczych Run",
        "bonus_sa %val%": "SA %val%%",
        "bonus_slow %val%": "Obniża SA przeciwnika o %val%",
        "bonus_stinkbomb %val% %crit%":
            "Obniżenie szansy na przebicie o %val%% i krytyka o %crit%%",
        "bonus_storm %val%": "%val% obrażeń od burzy błyskawic",
        "bonus_stun %val%": "Szansa na ogłuszenie %val%%",
        "bonus_sunreduction %val%": "Obrażenia od zimna i błyskawic -%val%%",
        "bonus_sunshield %val%": "Tarcza słońca +%val% pancerza, +%val2% leczenia",
        "bonus_thunder %val%": "%val% obrażeń od błyskawic",
        "bonus_truje %val%": "Trucizna, ujmuje %val% punktów życia",
        "bonus_wound %val% %dmg%": "Głęboka rana, %val%% szans na +%dmg% obrażeń",
        buys_all: "wszystko",
        buys_nothing: "nic",
        "capacity %val%": "Maksimum %val% sztuk razem",
        "choose skillpoints %amount%":
            "Wybierz na co przeznaczasz punkty zdolności.<br>Pozostało punktów: %amount%",
        client_ver_conflict_info:
            "<b>Niezgodna wersja klienta. Kliknij tutaj aby odświeżyć okno gry. <br />Jeśli komunikat sie powtarza wciśnij CTRL+R lub odśwież cache przeglądarki (CTRL+SHIFT+DEL - wybór odpowiedniej opcji).</b>",
        cl_mixtures: "Mikstury",
        copy_battle_log: "Kopiuj przebieg walki",
        "critical_dmg_help_txt %crit% %crit_val%":
            "Krytyk pomocniczy: <strong>%crit%%</strong><br />Moc krytyka pomocniczego: <strong>x%crit_val%</strong>",
        deputy: "Zastępca",
        disk_map_load_error:
            "Błąd ładowania mapy z dysku, sprawdź czy nie potrzebujesz aktualizacji plików map.",
        dragon_runes: "Smocze runy",
        "enemies %amount%": "%amount% przeciwników",
        "fatigue_negative %val%": "Odejmuje %val% min. od wyczerpania",
        "fatigue_positive %val%": "Dodaje %val% min. do wyczerpania",
        first_plan_buttons:
            "Użyj tych przycisków aby pokazać dany szereg na pierwszym planie",
        flee_item_description: "Umożliwia ucieczkę z walki",
        give_leadership: "Oddaj dowództwo",
        "gold_buy_confirm %sl% %gold%":
            'Czy na pewno chcesz kupić <strong>%gold%</strong> złota za <span style="color:red;font-weight:bold">%sl% SŁ</span> ?',
        gold_price_placeholder: "ile?",
        gold_shop_head_info: "Wymień SŁ na złoto",
        "gold_sl %sl% %gold%": "%sl% Sł - %gold% zł",
        group_kill_exp_count:
            "W tej drużynie naliczane będzie zabijanie grupowe w questach",
        group_kill_exp_nocount:
            "W tej drużynie nie będzie naliczane zabijanie grupowe w questach",
        guest_playtime_runout: "Czas gry dla gościa upłynął",
        "improves %items%": "Ulepsza %items%",
        improve_armor: "hełmy, buty, rękawice",
        improve_armorb: "zbroje, tarcze",
        improve_item_bound_info:
            "Ulepszany przedmiot automatycznie wiąże się z postacią!",
        improve_jewels: "pierścienie, naszyjniki",
        improve_weapon: "bronie",
        inactive_longtime: "od dawna nieaktywny(a)",
        "item_ac %val%": "Pancerz: %val%",
        "item_act %val%": "Odporność na truciznę %val%%",
        "item_dmg %val%": "Atak: %val%",
        "item_int %val%": "Atak magiczny",
        "item_pdmg %val%": "Atak fizyczny: %val%",
        "item_perdmg %val%": "Atak zwiększony o %val%%",
        item_recover_header: "Odzyskiwanie przedmiotów",
        "item_resfire %val%": "Odporność na ogień %val%%",
        "item_resfrost %val%": "Odporność na zimno %val%%",
        "item_reslight %val%": "Odporność na błyskawice %val%%",
        "item_sila %val%": "Atak+siła/%val%",
        "item_value %val%": "Wartość: %val%",
        "item_zr %val%": "Atak+zręczność/%val%",
        "itype %type% %value%": "%type% %value%",
        "last_mail_msg %from%": "Ostatnia wiadomość od:<br><b>%from%</b>",
        leave_group: "Opuść grupę",
        legbon_critred:
            "Krytyczna osłona: przyjmowane ciosy krytyczne są o 15% słabsze.",
        legbon_curse:
            "Klątwa: udany atak powoduje, iż przeciwnik otrzymuje 7% szans na chybienie przy jego następnym ataku.",
        legbon_dmgred: "Fizyczna osłona: obrażenia fizyczne zmniejszone o 12%.",
        legbon_holytouch:
            "Dotyk anioła: podczas udanego ataku 5% szansy na ogromne uleczenie ran, nie więcej niż stan początkowego życia.",
        legbon_lastheal:
            "Ostatni ratunek: kiedy po otrzymanym ataku zostanie graczowi mniej niż 12% życia, zostaje jednorazowo uleczony do 30-50% swojego życia.",
        legbon_pushback:
            "Odrzut: 8% szans na cofnięcie przeciwnika o krok do tyłu. Dotyczy wyłącznie profesji dystansowych.",
        legbon_resgain:
            "Ochrona żywiołów: 12% szans na podniesienie wszystkich odporności do maksimum (90%) przy przyjmowaniu ciosu magicznego.",
        "legbon_undefined %val%": "Nieznany bonus: %val%",
        legbon_verycrit:
            "Cios bardzo krytyczny: 10% szansy na podwojenie mocy ciosu krytycznego.",
        location_conquer_possible: "Możliwy podbój lokacji",
        "loot_with %day% %npc% %grpinf% %name%":
            "W dniu %day% został(a) pokonany(a) %npc% przez %name%%grpinf%",
        "lvl %lvl%": "Wymagany poziom: %lvl%",
        mail_box_full: "Skrzynka pocztowa pełna.",
        mail_item_description: "Wywołanie poczty",
        mascot_preview_info:
            "Kliknij na cyfrę pod maskotką, aby zobaczyć odpowiadającą jej akcję.",
        max_per_item: "Maks. za przedmiot:",
        message_sent: "Wiadomość wysłana.",
        "msg_+abdest %val%": "+Zniszczono %val% absorpcji",
        "msg_+acdmg %val%": "+Obniżenie pancerza o %val%",
        "msg_+actdmg %val%": "Obniżenie odporności na truciznę o %val%%",
        "msg_+crit": "+Cios krytyczny",
        "msg_+critsa %val%": "+Przyspieszenie o %val%% SA na +3 tury",
        "msg_+critwound": "+Ciężka rana",
        "msg_+dispel": "Przerwanie ciosu specjalnego.",
        "msg_+distract": "+Wytrącenie z równowagi",
        "msg_+endest %val%": "+Zniszczono %val% energii",
        "msg_+engback %val%": "%val% energii",
        "msg_+exp %val%": "Zdobyto łącznie %val% punktów doświadczenia.",
        "msg_+fastarrow": "+Szybka strzała",
        "msg_+firearrow": "+Płonąca strzała",
        "msg_+freeze": "+Zamrożenie",
        "msg_+hithurt %val%": "Bolesny cios, spowolnienie o %val%% SA",
        "msg_+injure %val%": "Zranienie (%val%)",
        "msg_+legbon_curse": "+Klątwa",
        "msg_+legbon_holytouch %val%": "+Dotyk anioła, życie +%val%",
        "msg_+legbon_pushback": "+Odepchnięcie",
        "msg_+legbon_verycrit": "+Cios bardzo krytyczny",
        "msg_+manadest %val%": "+Zniszczono %val% many",
        "msg_+of_crit": "+Cios krytyczny broni pomocniczej",
        "msg_+of_wound": "+Głęboka rana pomocnicza",
        "msg_+oth_cover %val% %name%": "%val% przejął(eła) %val% obrażeń.",
        "msg_+oth_dmg %val% %name%": " -%val%</b> obrażeń otrzymał(a) %name%.",
        "msg_+ph %val%": "Zdobyto łącznie %val% punktów honoru.",
        "msg_+pierce": "+Przebicie",
        "msg_+resdmg %val%": "+Obniżenie odporności o %val%%",
        "msg_+resdmgc %val%": "Obniżenie odporności na zimno  o %val%%",
        "msg_+resdmgf %val%": "Obniżenie odporności na ogień o %val%%",
        "msg_+resdmgl %val%": "Obniżenie odporności na błyskawice o %val%%",
        "msg_+stun": "+Ogłuszenie",
        "msg_+stun2": "+Potężne ogłuszenie",
        "msg_+stun2-c": "+Potężne zamrożenie",
        "msg_+stun2-d": "+Potężna przeszywająca strzała",
        "msg_+stun2-f": "+Potężne poparzenie",
        "msg_+stun2-l": "+Potężne porażenie",
        "msg_+swing": "+Szeroki zamach",
        "msg_+thirdatt %val%": "+%val%",
        "msg_+verycrit": "+Cios bardzo krytyczny",
        "msg_+wound": "+Głęboka rana",
        "msg_-absorb %val%": "-Absorpcja %val% obrażeń fizycznych",
        "msg_-absorbm %val%": "-Absorpcja %val% obrażeń magicznych",
        "msg_-arrowblock": "Strzała zablokowana",
        "msg_-blok %val%": "-Zablokowanie %val% obrażeń",
        "msg_-contra": "-Kontra",
        "msg_-evade": "-Unik",
        "msg_-legbon_critred": "-Krytyczna osłona",
        "msg_-legbon_dmgred %val%":
            "-Aktywna ochrona fizyczna %val%% obrażeń na całą walkę",
        "msg_-legbon_resgain": "-Ochrona żywiołów",
        "msg_-parry": "-Parowanie",
        "msg_-pierceb": "-Blok przebicia",
        "msg_-rage": "-Wściekłość",
        "msg_afterheal %name% %val%": "Przywrócono %val% punktów życia %name%.",
        msg_allslow: "Przeszywający krzyk.",
        msg_ansgame: "%name% rzuca czar! Wykonaj rzut obronny!",
        msg_arrowrain: "Deszcz strzał.",
        "msg_aura-ac %val%": "Aura ochrony fizycznej, pancerz+%val%.",
        "msg_aura-bandage %val%": "Bandażowanie ran %name% +%val% punktów życia.",
        "msg_aura-resall %val%": "Aura ochrony magicznej, odporności+%val%.",
        "msg_aura-sa %val%": "Aura szybkości SA%val%.",
        msg_blizzard: "Lodowa zamieć.",
        msg_cover: "Osłona kompana ciałem.",
        "msg_critwound %name% %val%": "%name%: %val% obrażeń z ciężkiej rany.",
        msg_disturb: "Rozproszenie przeciwnika.",
        "msg_dloot %name% %g1% %m1%":
            "%name% zdobył %g1% %m1%, jednak ze względu na przewagę poziomu przedmiot uległ zniszczeniu.",
        "msg_dmgdone %name1% %hpp% %val%": "%name1%(%hpp%%) uderzył# z siłą %val%",
        "msg_dmgtaken %name1% %hpp% %val%":
            "%name1%(%hpp%%) otrzymał$ %val% obrażeń",
        "msg_doubleshoot %name%": "%name% wykonał# podwójny strzał.",
        "msg_en-regen %gain_lost% %name% %val%":
            "%gain_lost% %val% energii %name%.",
        "msg_en-regen-cast %name% %target%":
            "%name% rzucił na %target% przywrócenie energii.",
        "msg_energy %name% %gain_loss% %val%": "%name% %gain_loss% %val% energii.",
        "msg_fire %name% %val%": "%name% otrzymał# %val% obrażeń od ognia.",
        "msg_fireshield %name%": "%name% rzucił tarczę ognia na %target%.",
        "msg_firewall %name%": "%name% rzucił# czar ściana ognia.",
        "msg_footshoot %name%": "%name% strzelił w stopę wroga: %target%.",
        "msg_frostshield %name%": "%name% rzucił tarczę mrozu na %target%.",
        "msg_heal %gain_lost% %name% %val%":
            "%gain_lost% %val% punktów życia %name%",
        "msg_healall %name% %val%": "%name% uzdrowił swoją drużynę (%val%).",
        "msg_injure %name% %val%": "%name%: %val% obrażeń po zranieniu.",
        "msg_insult %name% %name2% %val%": "%name% obraził %name2% na %val% tur.",
        "msg_legbon_lastheal %val%":
            "%val%: Ostatni ratunek, +%val2% punktów życia.",
        "msg_lightshield %name%": "%name% rzucił porażającą tarczę na %target%.",
        "msg_loot %name% %g1% %m1%": "%name% zdobył %g1% %m1%.",
        "msg_managain %name% %val%": "%name% otrzymał %val% many.",
        "msg_manatransfer %name% %val% %name2%":
            "%name% przekazał %val% many graczowi %name2%.",
        "msg_poison %name% %val%": "%name%: %val% obrażeń z trucizny.",
        "msg_prepare %name%": "%name% przygotowuje się do rzucenia %name2%.",
        "msg_reusearrows %val% %arrows%": "Odzyskano %val% %arrows% dla",
        msg_reusearrows_one: "Odzyskano jedną strzałę dla",
        "msg_shout %name%": "%name% rzucił# obraźliwe hasło w stronę przeciwnika.",
        "msg_soullink %name%": "%name% duchowo wspiera swoją drużynę.",
        "msg_step %name% %g1%": "%name% zrobił%g1% krok do przodu.",
        "msg_stinkbomb %name% %name2%":
            "%name% rzucił śmierdzący pocisk w przeciwnika %name2%.",
        "msg_storm %name%": "%name% wezwał# burzę z piorunami.",
        "msg_sunreduction %name%": "%name% rzucił odporność słońca na %target%.",
        "msg_sunshield %name%": "%name% rzucił tarczę słońca na %target%.",
        "msg_thunder %name%": "%name% wezwał# grom z nieba.",
        "msg_tspell %name%": "%name% wykonuje %name2%.",
        "msg_unknown_prameter %val%": "Nieznany parametr: <b>%val%",
        "msg_wound %name% %val%": "%name%: %val% obrażeń z głębokiej rany.",
        "msg_woundfrost %val%": "+Głęboka rana (-%val%% osłabiona magią zimna)",
        "m_crit_strenght %name% %val%":
            "Siła krytyka magii %name%: <strong>x%val%</strong>",
        nloc_heros_item_description:
            "Zlokalizowanie jednego herosa pasującego na twój poziom",
        nloc_monster_item_description: "Zlokalizowanie jednego potwora",
        nodepo_info: "Przedmiotu nie można przechowywać w depozycie ",
        nodesc: "Przedmiot niezidentyfikowany",
        notakeoff: "Czar niemożliwy do zdjęcia",
        no_activiti_logout_info:
            '<div>Zostałeś wylogowany z powodu braku aktywności.<br><br><button onclick="window.location.reload()">Wejdź do gry</button></div>',
        no_addon_comments: "Brak komentarzy",
        no_skillpoints_left: "Nie masz wolnych punktów zdolności",
        "offline_from %time%": "offline od %time%",
        oneenemy: "przeciwnika",
        outexchange: "Możliwość wymiany stroju na nowy.",
        outfit_1min: "1 minutę",
        "outfit_change_for %time%": "Zmienia twój wygląd na %time%",
        outfit_hrs1: " godziny",
        outfit_hrs2: " godzin",
        outfit_mins1: " minuty",
        outfit_mins2: " minut",
        outfit_perm: "stałe",
        part_arrows_plural1: "strzały",
        part_arrows_plural2: "strzał",
        part_gained: "Przywrócono",
        part_gain_en: "otrzymał",
        part_himself: "siebie",
        part_loss_en: "stracił",
        part_lost: "Stracono",
        pet_elite: "elita",
        pet_logout_hide: "Chowaniec znika po wyjściu z gry",
        pet_tasks: "Wykonuje polecenia:",
        privPage_save: "Zapisz",
        put_sl_turnedoff: "Wpłacanie SŁ zablokowane.",
        quest_started: "Rozpoczęto quest!",
        rebuy_price: "Cena skupu:",
        recovered:
            "Przedmiot odzyskany, obniżona wartość, nie może być wystawiany na aukcję",
        "reqgold %val%": "Wymagane złoto: %val%",
        "reqi %val%": "Wymagana intelekt: %val%",
        reqp: "Wymagana profesja ",
        "reqs %val%": "Wymagana siła: %val%",
        "reqz %val%": "Wymagana zręczność: %val%",
        "revive %amount%": "Skraca czas nieprzytomności o %amount%min.",
        rip_prefix: "Ś.p.",
        room_free: "wolne",
        seller_wont_buy_anything: "Ten sprzedawca nic od ciebie nie kupi.",
        seller_wont_buy_this: "Ten sprzedawca nie skupuje takich rzeczy.",
        selling_for_info: "Sprzedaż za:",
        sell_for_ph: "Sprzedaż za: <b>Punkty Honoru</b>.",
        sell_for_sl: "Sprzedaż za: <b>Smocze Łuski</b>",
        sell_prices: "Ceny sprzedaży",
        sell_price_cheap: "tanio",
        sell_price_expensive: "drogo",
        sell_price_normal: "normalnie",
        sell_price_vExpensive: "bardzo drogo",
        shopper_buys: "Skupuje:",
        shop_to_low_credits:
            "Masz zbyt mało Smoczych Łez by dokonać wybranego zakupu.",
        shop_to_low_gold: "Masz zbyt mało złota by dokonać wybranego zakupu.",
        "shop_to_low_items %type%":
            "Masz zbyt mało sztuk wymaganego środka płatności (%type%) by dokonać wybranego zakupu.",
        shop_to_low_ph:
            "Masz zbyt mało Punktów Honoru by dokonać wybranego zakupu.",
        "skills_actdmg %val%": "Obniża odporność na truciznę o %val%%",
        "skills_agi %val%": "Atak +%val%*zręczność",
        "skills_arrowrain %val%": "Wielokrotny atak o sile %val% obrażeń",
        "skills_bandage %val%": "Uleczenie %val% punktów życia",
        "skills_critsa %val%": "Przyspieszenie SA o %val%% po krytyku na 3 tury.",
        "skills_critslow %val%": "Spowolnienie SA o %val%0% po krytyku",
        "skills_critwound %val%": "Zranienie %val%% szans po krytyku",
        "skills_decevade %val%":
            "Szansa na unik przeciwnika zmniejszona o %val% punktów",
        "skills_distract %val%":
            "Szansa %val%% na wytrącenie z równowagi po krytyku",
        "skills_disturb %val%": "Obniżenie krytyka o %val%% i przebicia o %val2%%",
        "skills_engback %val%": "Odzyskanie %val%% energii po krytyku",
        "skills_fastarrow %val%": "%val%% szans 4-krotnie szybsze oddanie strzału",
        "skills_firebon %val%": "Atak ogniem +%val%*intelekt",
        "skills_fireshield %val%": "Pochłania %val%% obrażeń od zimna",
        "skills_footshoot %val%": "Krok do przodu jest wolniejszy o %val%%",
        "skills_frostbon %val%": "Atak zimnem +%val%*intelekt",
        "skills_frostpunch %val%": "Pchnięcie mrozu %val% obrażeń",
        "skills_frostshield %val%": "Pochłania %val%% obrażeń od ognia",
        "skills_healpower %val%": "Leczenie z konsumpcyjnych mocniejsze o %val%0% ",
        "skills_hpsa %val%": "Przyspieszenie o 20% przez %val% tur",
        "skills_injure %val%": "Szansa %val%% na zranienie przeciwnika",
        "skills_insult %val%": "Obraza przeciwnika na %val% tur(y)",
        "skills_lastcrit %val%": "Ostatni cios krytyczny +%val%%",
        "skills_lightbon %val%": "Atak błyskawicami +%val%*intelekt",
        "skills_lightmindmg %val%":
            "Obrażenia od błyskawic przynajmniej %val%% wartości maksymalnej",
        "skills_lightshield %val%": "Spowalnia atakującego o %val%% SA",
        "skills_longfireshield %val%": "Tarcza ognia utrzymuje się %val% tur",
        "skills_longfrostshield %val%": "Tarcza zimna utrzymuje się %val% tur",
        "skills_longlightshield %val%": "Tarcza błyskawic utrzymuje się %val% tur",
        "skills_of-thirdatt %val%": "%val%% szans na 3 atak",
        "skills_pcontra %val%": "%val%% szans na kontrę po parowaniu",
        "skills_poisonbon %val%": "Bonus %val% obrażeń do trucizny",
        "skills_rage %val% %turn%": "Wściekłość %val% %turn% po otrzymaniu krytyka",
        "skills_red-sa %val%": "Czas ataku -%val%% SA",
        "skills_redslow %val%": "Czas spowolnienia zmniejszony o %val%%",
        "skills_redstun %val%": "Czas ogłuszenia i zamrożenia zmniejszony o %val%%",
        skills_req_skill: "Wymagana umiejętność:<br>",
        skills_req_weapon: "Wymagana broń: ",
        "skills_reusearrows %val%": "Odzyskiwanie %val%% strzał po walce",
        "skills_sa-clothes %val%": "SA ubrań zwiększone o %val%%",
        "skills_set %val%": "Automatyczne wykonanie %val% tur w walce",
        "skills_shout %val%": "Obraza %val%",
        "skills_soullink %val%": "Przejęcie %val%% obrażeń",
        "skills_swing %val%": "Szansa %val%% na atak do 3 przeciwników",
        "skills_woundchance %val%": "+%val%% szans na głęboką ranę",
        "skills_wounddmgbon %val%": "Bonus %val% obrażeń do głębokiej rany",
        "skills_woundred %val%": "Obrażenia od głębokich ran zmniejszone o %val%%",
        "skill_of-str %val%": "Atak pomocniczy +%val%*siła",
        "skill_str %val%": "Atak %val%*siła",
        sl: "SŁ",
        solve_group: "Rozwiąż grupę",
        soulbound: "Wiąże po kupieniu",
        soulbound1: "Aktywny i związany z właścicielem",
        soulbound2: "Związany z właścicielem",
        split_impossible: "(Nie można dzielić)",
        split_possible: "(Można dzielić)",
        "stamina_shop choosen_option": "Zakup wyczerpania, wybrano opcję:",
        stamina_shop_click_nfo: "Kliknij na jedną z powyższych opcji",
        stamina_shop_click_nfo2: "Kliknij aby potwierdzić zakup",
        stamina_shop_cost: "Koszt:",
        stamina_shop_sure: "Jesteś pewien swego wyboru ?",
        teleport: "Teleportuje gracza",
        "temporary_acc_info %register_link%": "%register_link%",
        throwout_group_member: "Wyrzuć",
        "timelimit_can be used %val% minutes": "Można używać co %val% minuty",
        "timelimit_can be used %val% minutes2": "Można używać co %val% minut",
        "timelimit_can be used %val% sec": "Można używać co %val% sekund",
        "timelimit_can be used every min": "Można używać co 1 minutę",
        "timelimit_readyToUse_in %val% min": "Gotowy do użycia za %val% minut",
        "timelimit_readyToUse_in %val% sec": "Gotowy do użycia za %val% minuty",
        timelimit_readyToUse_inaminute: "Gotowy do użycia za minutę",
        timelimit_readyToUse_inawhile: "Gotowy do użycia za chwilę",
        timelimit_readyToUse_now: "Gotowy do użycia",
        townlimit: "Działa tylko w wybranych lokacjach",
        "ttl1 %date%": "Działa %val%min",
        "ttl2 %date%": "Zniknie za %val%min",
        turn: "tura",
        turns: "tury",
        type: "Typ: ",
        "types_list %upg_normal% %upg_uni% %upg_hero%":
            'Normalne: o %upg_normal%%<br><span class="improve unique">Unikatowe: o %upg_uni%%</span><br><span class="improve heroic">Heroiczne: o %upg_hero%%</span>',
        type_artifact: "artefakt",
        type_heroic: "heroiczny",
        type_legendary: "legendarny",
        "type_lower_req %val%": "zmniejszone wymagania o %val% lvl",
        "type_modification %val%": "ulepszenie %val%%",
        "type_modification_upgb %val% %name%": "Ulepszony o %val%% przez %name% ",
        type_modified: "ulepszony",
        type_unique: "unikat",
        unbind:
            "Odwiązuje związany przedmiot (aby odwiązać, przeciągnij na wybrany przedmiot w torbie)",
        "unconcious_info_txt %time%":
            "<b>Jesteś nieprzytomny.</b><br>Nie wiesz co się wokół Ciebie dzieje. Chyba ktoś Cię przenosi.<br>Ockniesz się, kiedy wrócą Ci siły, za <b>%time%s.</b>",
        undoupg:
            "Zdejmuje z przedmiotu wykonane ulepszenie lub obniżenie wymaganego poziomu (aby użyć, przeciągnij na wybrany przedmiot w torbie)",
        "unknown_stat %val%": "Nieznany stat: %val%",
        "upg_cost %gold% %sl%":
            'Koszt ulepszenia:<br /><span style="color:red">%gold% zł %sl%</span>',
        "url_chat_warning %url%":
            '<b style="color:red">Pamiętaj, aby NIGDY nie podawać swojego hasła<br>na stronach oferujących darmowe SŁ i złoto.</b> Podając tam hasło masz 100% pewności, że właściciel tej strony ukradnie z Twojego konta wszystko, co wartościowe.<br><br><center><b>Czy jesteś pewny, że chcesz wejść pod adres:</b><br><u style="color:#090">%url%</u> ?</center><br>',
        "valid_to %date%": "Ważny do: %val%",
        with_company: " wraz z drużyną",
        with_player: " wraz z kompanem",
        write_ok_to_confirm: "Wpisz OK aby potwierdzić",
        w_poison: "trucizna",
    },
    depo: {
        clan_depo: "Depozyt klanowy",
        "depo-balance": "Złoto w depozycie: ",
        "depo-txt-rent": "Wynajem do:",
        depotxt:
            "<br />- jedno powiększenie depozytu dodaje 112 miejsc na przedmioty<br />- powiększenie działa na wszystkich postaciach i światach<br />- powiększenie wykupuje się tylko raz, wysokość abonamentu za depozyt nie ulega zmianie<br />- członkowie KB oraz gracze, którzy zakupili przynajmniej 1000SŁ mogą powiększyć depozyt do 4x, pozostali 3x",
        depo_available_info: "Depozyt dostępny:",
        depo_clan_clan_upg_info:
            "Depozyt klanowy można wykupić po ulepszeniu klanu",
        depo_info_msg:
            "Koszt wynajmu depozytu na 1 miesiąc to<br>6SŁ od 20 poziomu postaci lub 1mln złota od 75 poziomu.</span><br>",
        depo_missing: "brak",
        depo_monthcost:
            "Koszt wynajmu depozytu na 1 miesiąc to<br>6SŁ od 20 poziomu postaci lub 1mln złota od 75 poziomu.",
        depo_msize_infoconfirm:
            "Czy na pewno chcesz powiększyć depozyt? Koszt jednego ulepszenia to 100SŁ (ze skarbca klanowego)",
        depo_msize_infoconfirm2: "Chcę wykupić depozyt!",
        depo_msize_infotxt:
            "Koszt depozytu to 100SŁ za każdą zakładkę (1 zakładka daje możliwość przechowywania 112 przedmiotów). Ilość zakładek nie może przekraczać rangi klanu. Smocze Łuski pobierane są ze skarbca klanowego.",
        depo_next_renew: "Następne przedłużenie do: ",
        depo_no_space: "Brak miejsca w depozycie",
        depo_no_space_inbag: "Nie masz wolnego miejsca w torbach",
        depo_no_space_intab: "Brak miejsca w wybranej zakładce",
        depo_remove_confirm_txt:
            "Czy na pewno chcesz usunąć depozyt?<br>Ta operacja jest nieodwracalna.",
        "depo_tabno_space ask":
            "Brak miejsca w wybranej zakładce, czy przejść do wolnej zakładki ?",
        depo_upgrade: "Powiększ depozyt",
        depo_upgrade_clan:
            "<b>Powiększ depozyt</b>koszt: 100SŁ ze skarbca klanowego",
        depo_upgrade_clan_confirm:
            "Czy na pewno chcesz powiększyć depozyt? Koszt jednego ulepszenia to 100SŁ (ze skarbca klanowego).",
        depo_upgrade_confirm:
            "Czy na pewno chcesz powiększyć depozyt? Koszt jednego ulepszenia to 45SŁ.",
        depo_upgrade_error: "Opłać depozyt aby miec możliwość jego rozszerzenia",
        low_lvl_to_paygold: "Masz zbyt niski poziom, by opłacić depozyt złotem!",
        low_lvl_to_usedepo: "Masz zbyt niski poziom, by korzystać z depozytu.",
        no_rights_to_see_cd: "Nie masz uprawnień do oglądania depozytu klanowego.",
    },
    eq_cl: {
        cl_armor: "Zbroje",
        cl_arrows: "Strzały",
        cl_bags: "Torby",
        cl_bastard: "Półtoraręczne",
        cl_bless: "Błogosławieństwa",
        cl_books: "Książki",
        cl_boots: "Buty",
        cl_distance: "Dystansowe",
        cl_gloves: "Rękawice",
        cl_gold: "Złoto",
        cl_helmets: "Hełmy",
        cl_helpers: "Pomocnicze",
        cl_improve: "Ulepszenia",
        cl_keys: "Klucze",
        cl_neclaces: "Naszyjniki",
        cl_neutral: "Neutralne",
        cl_onehanded: "Jednoręczne",
        cl_quests: "Questowe",
        cl_renewable: "Odnawialne",
        cl_rings: "Pierścienie",
        cl_shield: "Tarcze",
        cl_staffs: "Laski",
        cl_talisman: "Talizmany",
        cl_twohanded: "Dwuręczne",
        cl_usable: "Konsumpcyjne",
        cl_wands: "Różdżki",
    },
    eq_prof: {
        prof_bladedancer: "Tancerz ostrzy",
        prof_hunter: "Łowca",
        prof_mag: "Mag",
        prof_paladyn: "Paladyn",
        prof_tracker: "Tropiciel",
        prof_warrior: "Wojownik",
    },
    eq_w: {
        w_bastard: "półtoraręczna",
        w_distance: "dystansowa",
        w_fire: "ogniowa",
        w_frost: "zimna",
        w_helper: "pomocnicza",
        w_light: "błyskawic",
        w_meele: "biała",
        w_onehanded: "jednoręczna",
        w_shield: "tarcza",
        w_twohanded: "dwuręczna",
    },
    event_calendar: {
        d_friday: "Piątek",
        d_monday: "Poniedziałek",
        d_saturday: "Sobota",
        d_sunday: "Niedziela",
        d_thursday: "Czwartek",
        d_tuesday: "Wtorek",
        d_wednesday: "Środa",
        m_czerwiec: "Czerwiec",
        m_grudzien: "Grudzień",
        m_kwiecien: "Kwiecień",
        m_lipiec: "Lipiec",
        m_listopad: "Listopad",
        m_luty: "Luty",
        m_maj: "Maj",
        m_marzec: "Marzec",
        m_pazdziernik: "Październik",
        m_sierpien: "Sierpień",
        m_styczen: "Styczeń",
        m_wrzesien: "Wrzesień",
        show_calendar: "Pokaż kalendarz wydarzeń",
    },
    extManager: {
        addon_help_txt:
            'Dodatki widoczne tutaj są tworzone za pośrednictwem strony <a target=blank href="https://web.archive.org/web/20160212150005/http://addons2.margonem.pl">addons2.margonem.pl</a>, możesz tam zapisywać swoje dodatki.<br /><br />Możesz także instalować dodatki używając komendy "addon url" w konsoli, gdzie url to adres pliku javascript.<br /><br /><h2>Przydatne linki:</h2><div class="extMgrLinksList"><div><a href="https://web.archive.org/web/20160212150005/http://jquery.com/">jQuery</a> - strona popularnej biblioteki na której oparta jest gra Margonem</div><div><a href="https://web.archive.org/web/20160212150005/http://webhosting.pl/Kurs.JavaScriptu..Podstawy.programowania.na.potrzeby.stron.WWW">Kurs JavaScript</a> - rozbudowany kurs JavaScript dla początkujących</div><div><a href="https://web.archive.org/web/20160212150005/http://margoextend.net">Margo{<span style="color:forestgreen">extend</span>}</a> - strona z dodatkami do Margonem stworzona przez Docelufa, jednego z programistów gry</div><div><a href="https://web.archive.org/web/20160212150005/http://www.margonem.pl/?task=forum&show=posts&id=199732">Dodatki na forum</a> - temat na oficjalnym forum Margonem o dodatkach do gry</div><div><a href="https://web.archive.org/web/20160212150005/http://www.margonem.pl/?task=forum&show=posts&id=339459">Zmiany w kliencie</a> - temat informujący o zmianach w kliencie gry, które mogą spowodować błędy w istniejących dodatkach</div></div>',
        addon_like: "Polecam",
        addon_points_tip: "Punkty",
        addon_report: "Zgłoś dodatek jako szkodliwy lub niedziałający",
        addon_unlike: "Nie polecam",
        add_comment: "Dodaj komentarz",
        add_search: "Filtruj",
        ad_author: "Autor",
        ad_close: "Zamknij",
        ad_comment: "Dodaj",
        ad_install: "Instaluj",
        ad_opt_all: "Wszystkie",
        ad_opt_badged: "Promowane",
        ad_opt_popular: "Popularne",
        ad_opt_unverified: "Niesprawdzone",
        ad_opt_verified: "Sprawdzone",
        ad_type_txt: "Typ",
        ad_uninstall: "Odinstaluj",
        already_repoted:
            "Twoje poprzednie zgłoszenie nie zostało jeszcze rozpatrzone.",
        closeaddon_tip: "Zamknij okno dodatku",
        comment_added_to_mod_list: "Komentarz oczekuje na moderację.",
        comment_tip: "Dodaj komentarz",
        "created_at %time%": "Utworzony: %time%",
        default_comment_txt: "Kliknij aby dodać komentarz",
        give_comment_txt: "Wpisz treść komentarza",
        install_tip:
            "Zainstaluj dodatek<br />Zmiany będą widoczne po odświeżeniu okna gry.",
        loading_content: "Ładowanie dodatków...",
        no_image: "Brak obrazka",
        reported_successfully: "Dodatek został zgłoszony.",
        report_reason: "Powód zgłoszenia",
        report_short: "Powód zgłoszenia powinien być dłuższy.",
        uninstall_tip:
            "Odinstaluj dodatek<br />Zmiany będą widoczne po odświeżeniu okna gry.",
        update_me: "Aktualizować?",
        update_needed: "Dostępna nowa wersja!",
    },
    gtw: {
        "from_lvl %lvl%": " od %lvl%",
        gateway_availavle: "Przejście dostępne",
        "gateway_availavle_for %lvl%": "Przejście dostępne dla %lvl% poziomu",
        lvl_lvl: " poziomu",
        require_key: "Wymaga klucza",
        "to_lvl %lvl%": " do %lvl%",
    },
    help: {
        actions_head: "AKCJE:",
        actions_txt:
            '<strong>Poruszanie się</strong> - lewy przycisk myszy (pojedyncze kliknięcie lub wciśnięcie i przesuwanie), strzałki lub WSAD.<br />        <strong>Rozmowa z mieszkańcem</strong> - lewy przycisk myszy na jego postaci: <i>"Rozmawiaj"</i>.<br />        <strong>Podniesienie przedmiotu, zerwanie rośliny</strong> - należy stanąć na przedmiocie, lewy przycisk myszy na swojej postaci: <i>"Podnieś"</i>.<br />        <strong>Sprzedaż i handel przedmiotami</strong> - lewy przycisk myszki na przedmiot w ekwipunku.<br />        <strong>Kupno przedmiotu</strong> - lewy przycisk myszy na przedmiot w sklepie.<br />        <strong>Założenie przedmiotu</strong> - przeciągnięcie przedmiotu w odpowiednie pole zakładania ekwipunku.<br />        <strong>Spożycie jedzenia, mikstury</strong> - dwukrotnie lewy przycisk myszy na przedmiocie.<br />        <strong>Pisanie nowej wiadomości na czacie</strong> - Enter.<br />        <strong>Rozwinięcie czatu</strong> - litera C.<br /><br />        <strong style="color:#400; font-size:16px;">WALKA:</strong><br />        <strong>Rozpoczęcie walki</strong> - klikniecie na przeciwniku: lewy przycisk - walka turowa, prawy przycisk - walka szybka .<br />        <strong>Atak przeciwnika</strong> - prawy przycisk myszy na przeciwniku podczas walki, tylko <i>"Walka turowa"</i>.<br />        <strong>Krok do przodu</strong> - prawy przycisk myszy na swojej postaci podczas walki, tylko <i>"Walka turowa"</i>.<br />        <strong>Pobranie łupu</strong> - przycisk <i>"Potwierdź"</i>.<br />        <strong>Zamykanie ekranu walki</strong> - litera Z.<br />        <strong>Wybranie następnego przeciwnika</strong> - TAB<br />        <strong>Pościgi</strong> - TAB na mapach pvp wybiera przeciwnika, kliknięcie na nim rozpoczyna pościg.<br /><br />        <strong>Masz pytanie?</strong> - <a href="https://web.archive.org/web/20160212150005/http://www.margonem.pl/?task=help" target="_blank">FAQ i panel rozwiązywania problemów</a>.<br />        <strong>Pełna pomoc</strong> - <a target="_blank" href="https://web.archive.org/web/20160212150005/http://pomoc.margonem.pl/index.php?a=art_show&val=1">pomoc.margonem.pl</a>',
        battle_help_txt:
            "W trybie walki turowej klikamy na siebie lub przeciwnika aby wybrać rodzaj akcji.      <br><b>Kliknięcie prawym przyciskiem</b> myszy powoduje wykonanie domyślnej akcji.      Dla postaci własnej jest to krok do przodu, dla przeciwnika jest to atak.      <br><b>Kiedy postacie się zasłaniają</b> po lewej stronie okna walki są       przeźroczyste słabo widoczne przyciski. Klikając je rząd walczących postaci       przenosi się na pierwszy plan. Używa się tego w walkach z dużymi potworami,       które zasłaniają innych przeciwników.      <br>Okno walki można zamknąć klawiszem <b>Z</b>.      <br>Można zmienić rozmiar logu walki za pomocą klawisza <b>B</b>.",
    },
    ingame_register: {
        bm_advent: "Pokaż kalendarz adwentowy",
        bm_register: "Utwórz konto",
        collected_reward_info: "You have already collected this reward",
        collect_form: "Collect",
        collect_reward_info: "You can collect this reward now",
        complete_registration: "COMPLETE REGISTRATION",
        correct: "Correct",
        disabled_info: "Completed",
        disabled_info_short: "--",
        dont_show_again: "Don&#39;t show again",
        fill_in_fields: "Fill in those fields",
        register_finished: "Registration completed",
        register_info_short:
            "You&#39;re playing on the temporary account that will be lost after logging out. If you like the game make a permament account!",
        reg_birth_label: "Birth date",
        reg_email_label: "E-mail",
        reg_header: "REJESTRACJA",
        reg_login_label: "Login",
        reg_passwd_label: "Password",
        rew_head: "REWARDS",
        save_form: "Save",
    },
    item: {
        bag_drop_infotxt:
            '<center><span style="color:red;font-weight:bold;">Czy chcesz założyć tę torbę ?</span><br /><br /><strong>Tak</strong> - chcę założyć torbę (powoduje związanie przedmiotu)<br /><strong>Nie</strong> - umieść ją tylko w tym pojemniku</center><br />',
        cant_drop_spell: "Nie możesz zdjąć tego czaru",
        cant_wera_this: "Nie możesz tego założyć.",
        drop_bless_question: "Czy na pewno chcesz zdjąć błogosławieństwo?",
        "gold_limit_reach_info %loss%":
            "Uwaga, przekroczysz limit dostępnego złota, nadwyżka złota w ilości %loss% sztuk zostanie stracona. Czy chcesz kontynuować ?",
        "item_split %max%": "Podziel przedmiot, maksymalna ilość: %max%",
        quest_item_cant_split: "Przedmiotów questowych nie można dzielić.",
        split_bad_value: "Podano nieprawidłową wartość!",
        this_item_cant_split: "Tego przedmiotu nie da się podzielić.",
        turn_off: "Wyłącz",
        use_it: "Użyj",
    },
    itemtip: {
        furniture: "Mebel - kliknij będąc w swoim pokoju aby użyć.",
    },
    loader: {
        items: "Przedmioty",
        location: "Lokacja",
        map_file: "Plik mapy",
        npc: "NPC",
        player: "Gracz",
    },
    loot: {
        "bag_room %amount%": "Miejsca w torbie:<br>%amount%",
        dont_want: "Nie chcę",
        really_want: "Koniecznie potrzebuję",
        want: "Chcę",
    },
    mails: {
        "ago %ago%": "%ago% temu",
        attachments: "Załączniki:",
        chats_left: "Znaków",
        "clanmsg_info %clan_rcp_name%":
            'Aby wysłać wiadomość do klanowiczów wpisz w pole adresata: "%clan_rcp_name%"',
        clanmsg_rcp_name: "klanowicze",
        del_message: "Usuń wiadomość",
        fraud_possible: "Możliwa próba oszustwa!",
        from: "Od: ",
        from_tip: "Odpowiedz nadawcy",
        get_attachment: "Pobierz załącznik",
        get_attachment_del: "Pobierz załącznik<br>i usuń wiadomość",
        "gold_att %amount%": "Złoto: %amount%",
        gold_attachment: "Złoto:",
        item_attachment: "Przedmiot:",
        no_attach: "brak",
        send_cost: "(koszt: 100 złota)",
        "sl_att %amount%": "SŁ: %amount%",
        to: "Do:",
    },
    map: {
        click_to_show_killers: "Kliknij aby zobaczyć listę dedaczy",
        click_to_show_locationdata: "Kliknij aby pokazać dane lokacji",
        conquer_loc_stats: "Statystyki podbitych lokacji:",
        "location_conquered %percent%": "Lokacja podbita w: %percent%% ",
        "location_lost %percent%": "Lokacja stracona w: %percent%% ",
        "loc_conquer %val% %exp%":
            "Lokacja podbita w: %val%% <br />Zwiększenie doświadczenia: %exp%x",
        "loc_lost %val% %exp%":
            "Lokacja stracona w: %val%% <br />Zmniejszenie doświadczenia: %exp%x",
        "multiplier_counter+ %val%": "Zwiększenie doświadczenia: %val%x",
        "multiplier_counter- %val%": "Zmniejszenie doświadczenia: %val%x",
        my_character: "Moja postać",
        my_char_wanted: "Poszukiwany listem gończym",
        no_data: "Brak danych...",
        pvp_arena: "Arena",
        pvp_ask: "PvP za zgodą",
        pvp_off: "PvP wyłączone",
        pvp_on: "Mapka PvP",
        you_are_here: "Jesteś tutaj",
    },
    menu: {
        attack: "Atakuj",
        attack_turn: "Walcz turowo",
        crimson_bless: "Karmazynowe błogosławieństwo",
        e2jump_over: "Omiń",
        emo_mad: "Złość się",
        go: "Przejdź",
        kiss: "Pocałuj",
        lookat: "Obejrzyj",
        run: "Uruchom",
        take: "Podnieś",
        talk: "Rozmawiaj",
        team_invite: "Zaproś do drużyny",
        trade: "Handluj",
    },
    minigames: {
        mastermind_infoB_tip:
            "Liczba dobrze wybranych kolorów, ale na niewłaściwych miejscach",
        mastermind_infoC_tip: "Ilość pozostałych prób ułożenia kodu",
        mastermind_infotxt:
            "Ułóż tajny kod! Kieruj się podpowiedziami:<br />- A - liczba kolorów na właściwych miejscach.<br />- B - liczba dobrze wybranych kolorów na, ale na niewłaściwych miejscach.<br />- C - ilość pozostałych prób ułożenia kodu.",
        masterming_infoA_tip: "Liczba kolorów na właściwych miejscach",
        pipes_desc:
            "Pomóż kulce dostać się z jednej strony na drugą. Przeciągaj rurki we właściwe miejsca, by połączyć dwie duże rury!",
        questions_time: 'Pozostało <span class="timer"></span> na odpowiedź',
        saper_chance_left: "Ilość szans:",
        saper_infotxt:
            "Odkryj wszystkie zielone kryształy, uważaj, aby nie trafić na czerwone. Każdy kryształ ma numer, który określa, ile pól wokół niego zawiera w sobie zielony kryształ (nie dotyczy przekątnych).",
        saper_left_fields: "Pozostałe pola:",
    },
    motel: {
        "all**": "wszystkie**",
        amount_th: "Ilość",
        keysadd_opt: "Dorób klucze",
        keysrm_opt: "Usuń klucze",
        long_opt: "Przedłuż",
        "price* %p1%": "%p1% SŁ<br>%p1%k złota<br>%p1%0k złota",
        "price2* %p1%": "%p1% SŁ",
        price_th: "Cena*",
        rent_this: "Wynajmij",
        rm_1month: "miesiąc",
        rm_2months: " miesięcy",
        "rm_2months*": " miesiące",
        "room_confirm_question %time%":
            "Czy jesteś pewien, że chcesz wynająć ten pokój<br>na %time%?",
        room_info_txt:
            "Cena za jednostkę (1 miesiąc, 1 klucz)<br>**Poza tymi we własnym ekwipunku",
        room_key_remove_confirm:
            "Czy jesteś pewien, że chcesz usunąć wszystkie klucze<br>do tego pokoju?",
        room_taken: "zajęte",
        room_th: "Pokój",
        state_th: "Stan",
        time_12m: "12m-cy",
        time_1m: "1m-c",
        time_2m: "2m-ce",
        time_3m: "3m-ce",
        time_6m: "6m-cy",
    },
    music_interface: {
        on_off: "Włącz/wyłącz losowanie ścieżki dźwiękowej",
        quality_change: "Zmień jakość dźwięku",
        start_stop: "Start/Stop",
        volume_change: "Ustaw poziom głośności muzyki",
    },
    npc: {
        eliteI: "elita",
        wt_elite3: "elita III",
        wt_hero: "heros",
        wt_titan: "tytan",
    },
    opts: {
        opt_1: "Blokuj wiadomości prywatne",
        opt_10: "Nie upominaj za błędy na czacie",
        opt_12: "Nie przechodź automatycznie przez przejścia",
        opt_13: "Nie pokazuj podświetleń rang przedmiotów",
        opt_14: "Blokuj zaproszenia do drużyn spoza przyjaciół i klanu.",
        opt_2: "Blokuj zaproszenia do klanu i zgłoszenia dyplomacji",
        opt_3: "Blokuj handel z innymi graczami",
        opt_4: "Kiedy atakuje potwór pozwalaj wybrać tryb walki",
        opt_5: "Blokuj prośby o akceptację przyjaciół",
        opt_6: "Blokuj pocztę od nieznajomych",
        opt_7: "Wyłącz chodzenie myszką",
        opt_8: "Wyłącz efekty animacji",
        opt_9: "Nie informuj o logowaniu się klanowiczów",
    },
    pet: {
        are_u_sure: "Czy jesteś pewien ?",
        click_to_show_special_actions:
            "Kliknij aby zobaczyć dostępne akcje specjalne",
        elite: "elita",
        elite_her: "heroiczny",
        elite_leg: "legendarny",
        menu_comeafter: "Idź za mną",
        menu_hide: "Schowaj",
        menu_standbehind: "Stań za mną",
        menu_standfront: "Stań przede mną",
        menu_standleft: "Stań po lewej",
        menu_standright: "Stań po prawej",
        "owner %name%": "Właściciel: %name%",
    },
    pet_tip: {
        pet_elite: "elita",
        pet_heroic: "heroiczny",
        pet_legendary: "legendarny",
    },
    pklist: {
        add_observed: "Dodaj do listy obserwowanych",
        change_pos: "Zmień pozycję",
        clear_list: "Wyczyść listę",
        list_empty: "Lista jest pusta...",
        loading_list: "Wczytywanie listy...",
        observed_limi_reached:
            "Osiągnięto limit obserwowanych, usuń kogoś z listy aby dodać następnego.",
        open_list: "Otwórz listę",
        pklist_header: "Lista graczy poszukiwanych listem gończym:",
        pk_filter: "Filtruj: ",
        remove_observed: "Usuń z listy obserwowanych",
        show_list: "Zobacz listę",
        wanted_info: "Poszukiwany<br/>listem gończym",
    },
    player: {
        base_stats: "Statystyki bazowe:",
        experience: "Doświadczenie",
        goldlimit: "Limit złota:",
        life_points: "Punkty życia",
        "to %lvl% %exp%": "Do %lvl% poziomu:</B>%exp%",
    },
    premium_panel: {
        premium_item_0: "Mikstury leczące",
        premium_item_1: "Ulepszenia przedmiotów",
        premium_item_10: "Strzały",
        premium_item_11: "Kamienie teleportujące",
        premium_item_12: "Błogosławieństwa",
        premium_item_13: "Maskotki",
        premium_item_14: "Stroje",
        premium_item_15: "Torby, saszetki na klucze i talizmany",
        premium_item_2: "Zakup wyczerpanie",
        premium_item_3: "Zakup złota",
        premium_item_4: "Buty",
        premium_item_5: "Hełmy i kapelusze",
        premium_item_6: "Rękawice i karwasze",
        premium_item_7: "Zbroje, kaftany i płaszcze",
        premium_item_8: "Naszyjniki",
        premium_item_9: "Pierścienie",
    },
    promocodes: {
        multiple_group_info: "Wybierz jedną z nagród",
        promo_choose: "Wybierz!",
        promo_expired: "Kod jest już nieaktualny",
        promo_or: "lub",
        promo_single_use_info:
            "Z promocji można skorzystać tylko raz w obrębie całego konta.",
    },
    quest: {
        daily_quest: "Quest dzienny.",
        quest_cancel_confirm: "Czy na pewno chcesz porzucić to zadanie?",
        quest_placeholder_item:
            "Tutaj pojawi się %NAME%<br>Poczekaj lub wróć później",
        quest_placeholder_npc:
            "Tutaj pojawi się %NAME%<br>Poczekaj lub wróć później",
    },
    ranks: {
        admin: "Administrator",
        chat_mod: "Moderator",
        mg: "Mistrz Gry",
        super_chat_mod: "Super moderator",
        super_mg: "Super MG",
    },
    recover: {
        accept: "Potwierdź!",
        confirm_recover: "Potwierdź!",
        "item_destroyed %timeago%": "Zniszczony %timeago% temu",
        item_recover_info:
            "- Przywrócenie dowolnego przedmiotu kosztuje 1 SŁ<br />- Po przywróceniu przedmiot ma wartość 1 sztuki złota, jest związany z postacią oraz nie może być wystawiony na aukcje",
        no_items_on_list: "Brak przedmiotów na liście...",
        no_items_to_recover: "Brak przedmiotów do odzyskania...",
        recover_1sl: "Przywróć za 1 sł",
    },
    reload: {
        "auto_reload in":
            'Przeładowanie strony za <span id="reconnectInfo">20s</span>',
        canceled: "[anulowano]",
        server_connection_fail:
            "Brak odpowiedzi serwera, sprawdź połączenie internetowe. Trwają automatyczne próby wznowienia połączenia",
    },
    skills: {
        add_to_list: "Dodaj do listy",
        bm_limit_reached: "Osiągnięto maksymalny limit umiejętności na liście",
        bm_normal_attack: "Zwykły atak",
        bm_remove_fromlist: "Usuń z listy",
        edit_fight_mastery:
            '<center>Edytuj ustawienia umiejętności<br />"Mistrzostwo walk"</center>',
        edit_sk: "EDYTUJ",
        "learn_cost %cost%": "Koszt nauki: %cost%zł",
        lear_skill_tip: "Jest to koszt wykorzystania jednego punktu umiejętności",
        nxt_lvl: "NASTĘPNY POZIOM",
    },
    static: {
        addons_active: "Aktywne dodatki",
        addon_active: "Zainstalowane dodatki",
        addon_editown: "Edycja własnego dodatku",
        addon_list_h2: "Lista dodatków",
        addon_list_mine: "Moje dodatki",
        addon_list_unav: "W tej chwili niedostępne.",
        addon_name: "Nazwa:",
        addon_run: "Uruchom:",
        addon_save: "Zapisz:",
        addon_start_header: "Krótkie wprowadzenie do dodatków",
        addon_start_txt:
            'Aby tworzyć własne dodatki trzeba znać jQuery, gdyż tej biblioteki używa klient Margonem.      Do projektowania dodatków przyda się <b>konsola.</b>       Jeśli dana komenda nie jest komendą konsolową system traktuje ją jako JS.      Przydatna będzie więc komenda dump służąca do wyświetlania zawartości zmiennych.      Poza tym atrybut tip służy do pokazywania tzw. dymków ponad elementami html, np. <img src="..." tip="Test">.',
        bm_movie_tip: "Okienko z filmem",
        bm_party_tip: "Jesteś w drużynie",
        chathorror_cancel: "Anuluj wysłanie",
        chathorror_config: "(upominanie za błędy można wyłączyć w konfiguracji)",
        chathorror_header:
            'Lista nieistniejących wyrazów:<br><span style="color:gold"></span><br><br>Wpisz powyższy(e) wyraz(y) poprawnie:',
        chathorror_send: "Wyślij wiadomość z błędami",
        for_30days: 'Na 30 dni od jutra: <b style="color:red">8SŁ</b>',
        for_today: 'Na dziś: <b style="color:red">1SŁ</b>',
        for_week: 'Na tydzień od jutra: <b style="color:red">2SŁ</b>',
        hide: "ukryj",
        item_drop_destroy: "Zniszczyć",
        item_drop_nothing: "Nic",
        item_drop_question: "Co chcesz zrobić z tym przedmiotem?",
        item_drop_throw: "Wyrzucić*",
        "item_throw_*info":
            "* Wyrzucenie objęte jest podatkiem 100 złota za przedmiot.",
        "just_while...": "Momencik...",
        loading_inprogress: "Trwa ładowanie gry...",
        loading_inprogress_long:
            "Jeżeli za pół minuty gra nie załaduje się, naciśnij klawisz F5",
        mytr_gold_tip: "Enter akceptuje<br>Esc cofa zmianę",
        stamina_renew_4h: "Zwiększ swój zapas wyczerpania o 4 godziny:",
        warn_tip: "Nowa wiadomość w konsoli",
        yt_close: "zamknij",
    },
    talk: {
        dialog_next: "Dalej",
        end_talk1: "Zakończ rozmowę",
        end_talk2: "Koniec",
        reward_header: "Nagrody:",
        rew_exp: "Doświadczenie:",
        rew_gold: "Złoto:",
        rew_item: "Przedmiot:",
        rew_ph: "PH:",
    },
    time_diff: {
        "time_days %val%": "%val% dni",
        "time_h %val%": "%val% h",
        "time_min %val%": "%val% min.",
    },
    trade: {
        gold_low: "Nie masz tyle złota!",
        player_delay_trade: "Nie możesz jeszcze handlować z tym graczem.",
    },
    tutorials: {
        finish: "Zakończ",
        next: "Dalej",
        skip: "Pomiń",
        turn_off: "Wyłącz",
        turn_off_nfo:
            "Wyłącza samouczek, możesz go później włączyć w konfiguracji na stronie głównej.",
        "t_0.0":
            "Witaj! Jestem Makina, będę Twoją przewodniczką po krainie Margonem! Właśnie ocknąłeś się po długiej chorobie spowodowanej ranami zadanymi przez potwory.",
        "t_0.1":
            "Aby poruszać się użyj strzałek lub kliknij myszką w miejsce, do którego chciałbyś dojść. Aby porozmawiać z mieszkańcem, podejdź do niego i kliknij na postać.",
        "t_0.2":
            'W każdej chwili możesz wybrać przycisk <a href="#" onclick="showHelp(); return false;">Sterowanie</a>, aby przypomnieć sobie, jak wykonuje się jakąś akcję w grze.',
        "t_1.0":
            "Świetnie! Właśnie rozmawiasz z mieszkańcem krainy Margonem. Możesz się wiele od niego dowiedzieć, wybieraj pytania lub zakończ dialog, klikając na odpowiedź podświetloną na żółto.",
        "t_10.0":
            "Mapa w krainie Margonem jest jednym z najważniejszych narzędzi, jakich będziesz używał w swoich podróżach! Migający obszar oznacza lokację, w której się znajdujesz obecnie.",
        "t_10.1":
            "Jednak możesz wybrać mapę bieżącej lokacji np.: by znaleźć z niej wyjście. Twoja pozycja będzie oznaczona czerwoną kropką. Lista lokacji pomoże odnaleźć Ci miejsce o konkretnej nazwie na mapie.",
        "t_11.0":
            "Gracze w krainie Margonem mogą zrzeszać się w klany. Tutaj możesz obejrzeć ich strony główne oraz listy członków. Jeśli będziesz członkiem klanu tutaj znajdziesz dodatkowe możliwości, jakie daje klan.",
        "t_12.0":
            "To coś bardzo ciekawego! Tutaj możesz dodawać osoby, które uważasz za przyjaciół lub wrogów, wpisując ich nicki i naciskając plusa. Przyjaciel będzie musiał wyrazić zgodę, by znaleźć się na Twojej liście.",
        "t_12.1":
            "Dzięki tej liście, wiesz gdzie są Twoi przyjaciele lub kiedy ostatnio byli w grze. Za to wrogowie nie mogą wysyłać do Ciebie wiadomości, ani pisać prywatnie.",
        "t_13.0":
            "W Margonem można tworzyć własne dodatki do gry w bibliotece jQuery. To bardzo ciekawa funkcja. Warto o tym poczytać na forum.",
        "t_14.0":
            "No nieźle! Chcesz otworzyć się na innych? Czat umożliwia komunikowanie się z graczami przebywającymi w tym samym miejscu co Ty. Aby zacząć pisać na czacie ogólnym wystarczy nacisnąć Enter.",
        "t_14.1":
            "Naciskając przycisk Chat lub literę C, zmieniasz rozmiar czatu, do takiego jaki w danej chwili Ci odpowiada. ",
        "t_14.2":
            "Aby napisać prywatną wiadomość do kogoś należy na czacie zacząć od @nick. Jeśli chcesz napisać do członków swojego klanu, zaczynasz od /k.",
        "t_14.3":
            "Pamiętaj! Za nieodpowiednie słownictwo, żebranie i wulgaryzmy, dostęp do czatu ogólnego może zostać czasowo zablokowany!",
        "t_15.0":
            "W krainie Margonem są różne rodzaje lokacji. Obecnie znajdowałeś się w lokacjach zielonych, w których nie może Cię nikt zaatakować. Istnieją jeszcze lokacje: żółte, czerwone i pomarańczowe.",
        "t_15.1":
            "W lokacji żółtej istnieje możliwość walki, jeśli dwaj gracze wyrażą na nią zgodę. Zgoda wyrażana jest przez ustawienie mieczy, odmowa przez ustawienie tarczy.",
        "t_15.2":
            "W czerwonych lokacjach gracze mogą walczyć ze sobą w każdym przypadku, niezależnie od ustawienia tarczy lub mieczy.",
        "t_15.3":
            "Pomarańczowa lokacja to arena. Możesz na niej spróbować sił z innymi graczami. Jeśli przegrasz pojawiasz się znowu na tej arenie po kilku sekundach. Arena jest w każdym mieście.",
        "t_16.0":
            "Brawo! Można już powiedzieć, że jesteś doświadczonym graczem! Od teraz będziesz mógł sam rozdawać 1 punkt statystyk wedle uznania i możesz zacząć uczyć się umiejętności.",
        "t_16.1":
            "Umiejętności to specjalne sposoby walki zależne od profesji i poziomu. Dzielą się na: Drogę Siły, Drogę Sprytu, Drogę Żywiołów i Drogę Światłości..",
        "t_16.2":
            "Nauczyciele umiejętności znajdują się w różnych miejscach: kowal Aberite uczy Drogi Siły i jest w swojej kuźni w Przedmieściach Karka-han.",
        "t_16.3":
            "Drogi Sprytu uczy łowca Shagarat z Torneg, Drogą Żywiołów zajmuje się mag Jaren z Baraków w Ithan, a Drogę Światłości zna paladyn sir William z Ratusza Karka-han.",
        "t_17.0":
            "Inni gracze mogą zapraszać Cię do klanów, listy przyjaciół lub handlu. Jeśli przeszkadzają Ci takie komunikaty, możesz je zablokować w Konfiguracji. Znajdziesz tam również inne funkcje.",
        "t_18.0":
            "Jesteś gotowy, aby zobaczyć wioskę! Stań na ikonie drzwi i poznaj jej mieszkańców!",
        "t_19.0":
            "Możesz szybko zakończyć walkę klikając przycisk auto. Powoduje to przejście walki w tryb automatyczny i szybsze jej zakończenie. Skrót: A",
        "t_2.0":
            "Dobra robota! Teraz możesz porozmawiać z innymi mieszkańcami lub zdobyć doświadczenie w walce. Znajdź jakieś zwierzę na 1 poziomie doświadczenia. Są to zazwyczaj króliki, zające, wiewiórki itp. Na razie nie próbuj walczyć z niczym silniejszym!",
        "t_2.1":
            "Aby rozpocząć walkę, należy podejść do zwierzęcia i kliknąć na nie lewym przyciskiem myszy. Walka przebiegnie automatycznie, po jej zakończeniu pokaże się okienko łupów. Jeśli zaakceptujesz łup, zostanie przeniesiony do plecaka. Okienko walki zamkniesz, klikając Zamknij. Zwierzę po upływie kilku chwil pojawi się znowu w tym miejscu.",
        "t_2.2":
            "Jeśli się zgubisz, użyj mapy świata Margonem. Jeśli będziesz chciał coś komuś powiedzieć, naciśnij Enter i zacznij pisać wiadomość.",
        "t_3.0":
            "Brawo! Zdobyłeś swój pierwszy przedmiot! Jeśli jest konsumpcyjny, to możesz go spożyć, klikając na niego dwukrotnie lub przeciągając w miejsce założenia broni i pancerza. Zjedzenie takiego przedmiotu pozwala odzyskać kilka punktów życia. Zdobyte przedmioty warto sprzedać w sklepie, aby zdobyć złoto na wyposażenie.",
        "t_3.1":
            "Przedmioty zdobywa się nie tylko w walkach. Można zrywać kwiaty i owoce. Aby to zrobić stań na roślinie, naciśnij na swoją postać i wybierz Podnieś.",
        "t_4.0":
            "Zdobyłeś następny poziom doświadczenia! Punkty statystyk zostały rozdane automatycznie, później będziesz mógł to robić sam. Jesteś silniejszy, więc warto pomyśleć o zakupie broni i pancerza. W każdej wiosce jest handlarz lub sprzedawca, który posiada sklep z potrzebnymi rzeczami.",
        "t_5.0":
            "Stoczyłeś wiele walk, ale nikt nie jest niezniszczalny. W każdej wiosce znajduje się uzdrowiciel, który opatrzy rany i przywróci wszystkie punkty życia. W przypadku wioski magów należy napić się uzdrawiającej wody ze studni. Po odzyskaniu wszystkich punktów życia, możesz dalej kontynuować swoją walkę.",
        "t_6.0":
            "Zostałeś pokonany! Ale nie martw się! Na początku każdy ma wzloty i upadki. Za chwilę znowu się ockniesz. Pamiętaj, by zaraz po tym udać do uzdrowiciela!",
        "t_7.0":
            "Zdecydowałeś się na zakupy? Najpierw musisz zdobyć złoto. Aby przenieść przedmiot ze swojego plecaka do sklepu należy na niego kliknąć. Pojawi się wtedy w polu sprzedaż. Po akceptacji przedmioty zostaną sprzedane. Ilość złota podana jest pod paskiem zdrowia i doświadczenia.",
        "t_7.1":
            "Jeśli masz już dość złota, warto byłoby coś wybrać. Zacznij od broni i pancerza. Zwróć uwagę, że przedmioty wymagają konkretnego poziomu doświadczenia i wybierz takie, których będziesz mógł używać.",
        "t_7.2":
            "Aby założyć broń lub pancerz należy przedmiot przeciągnąć na odpowiedni kwadracik znajdujący się nad ekwipunkiem. Pamiętaj, że do łuku musisz posiadać strzały!",
        "t_7.3":
            "Dzięki broni i pancerzowi będziesz mógł walczyć z silniejszymi zwierzętami, przez co będziesz zdobywał więcej doświadczenia i otrzymywał mniejsze obrażenia.",
        "t_8.0":
            "Wspaniale! Otrzymałeś zadanie do wykonania! Aby zobaczyć wskazówki należy wybrać przycisk Aktywne questy znajdujący się nad ekwipunkiem. Postępuj według nich, a na pewno otrzymasz nagrodę i doświadczenie za swój wysiłek!",
        "t_9.0":
            "Uważaj! Na tutejszych bezdrożach możesz natknąć się na przerażające potwory i niebezpieczne zwierzęta! Lepiej wróć do wioski, nabierz sił i doświadczenia, a wtedy cały świat stanie przed Tobą otworem!",
    },
};

const _t = (a, b, c) => {
    const d = isset(c) ? c : "default";
    if (isset(__translations[d]) && isset(__translations[d][a])) {
        let e = __translations[d][a];
        if (isset(b)) for (const f in b) e = e.replace(f, b[f]);
        return e;
    }
    return "[T:" + d + "]" + a;
};

const hero = {
    lvl: 500,
};
const roundParser = function (a) {
    a = Math.floor(a);
    let b = a.toString().length % 3,
        c = a.toString().length,
        d = "",
        e = a / Math.pow(10, c > 9 ? 9 : c - (0 == b ? 3 : b)),
        f = {
            0: "",
            3: "k",
            6: "m",
            9: "g",
        };
    for (const g in f) c > parseInt(g) && (d = f[g]);
    return {
        val: e,
        postfix: d,
    };
};
const round = function (a, b, c) {
    (b = isset(b) ? b : 1), (a = Math.abs(parseFloat(a)));
    let d = 0 > a ? "-" : "",
        e = "";
    switch (b) {
        case 10:
            isset(c) || (c = " "),
                (e =
                    a.toString().length < 5
                        ? a
                        : a.toString().replace(/(\d)(?=(?:\d{3})+$)/g, "$1" + c));
            break;
        default:
            var f = roundParser(a);
            if (
                (isset(c) || (c = "."),
                    (e =
                        Math.round(f.val * Math.pow(10, b - 1)) / Math.pow(10, b - 1) +
                        f.postfix),
                    (e = e.replace(/\./, c)),
                9995 > a)
            )
                return d + a;
    }
    return d + e;
};

const eq = {
    wx: [37, 0, 37, 74, 0, 37, 74, 37, 0, 0],
    wy: [0, 36, 36, 36, 72, 72, 72, 108, 108, 0],
    classes: [
        "",
        _t("cl_onehanded", null, "eq_cl"),
        _t("cl_twohanded", null, "eq_cl"),
        _t("cl_bastard", null, "eq_cl"),
        _t("cl_distance", null, "eq_cl"),
        _t("cl_helpers", null, "eq_cl"),
        _t("cl_wands", null, "eq_cl"),
        _t("cl_staffs", null, "eq_cl"),
        _t("cl_armor", null, "eq_cl"),
        _t("cl_helmets", null, "eq_cl"),
        _t("cl_boots", null, "eq_cl"),
        _t("cl_gloves", null, "eq_cl"),
        _t("cl_rings", null, "eq_cl"),
        _t("cl_neclaces", null, "eq_cl"),
        _t("cl_shield", null, "eq_cl"),
        _t("cl_neutral", null, "eq_cl"),
        _t("cl_usable", null, "eq_cl"),
        _t("cl_gold", null, "eq_cl"),
        _t("cl_keys", null, "eq_cl"),
        _t("cl_quests", null, "eq_cl"),
        _t("cl_renewable", null, "eq_cl"),
        _t("cl_arrows", null, "eq_cl"),
        _t("cl_talisman", null, "eq_cl"),
        _t("cl_books", null, "eq_cl"),
        _t("cl_bags", null, "eq_cl"),
        _t("cl_bless", null, "eq_cl"),
        _t("cl_improve", null, "eq_cl"),
    ],
    prof: {
        w: _t("prof_warrior", null, "eq_prof"),
        p: _t("prof_paladyn", null, "eq_prof"),
        m: _t("prof_mag", null, "eq_prof"),
        h: _t("prof_hunter", null, "eq_prof"),
        b: _t("prof_bladedancer", null, "eq_prof"),
        t: _t("prof_tracker", null, "eq_prof"),
    },
    weapon: {
        sw: _t("w_meele", null, "eq_w"),
        "1h": _t("w_onehanded", null, "eq_w"),
        "2h": _t("w_twohanded", null, "eq_w"),
        bs: _t("w_bastard", null, "eq_w"),
        dis: _t("w_distance", null, "eq_w"),
        fire: _t("w_fire", null, "eq_w"),
        light: _t("w_light", null, "eq_w"),
        frost: _t("w_frost", null, "eq_w"),
        sh: _t("w_shield", null, "eq_w"),
        h: _t("w_helper", null, "eq_w"),
        poison: _t("w_poison"),
    },
};

function _l() {
    return "pl";
}

export function itemRarity(stat) {
    if (!stat) {
        return '';
    }
    const d = stat.split(";");

    for (const e in d) {
        if (['unique', 'heroic', 'legendary', 'artefact', 'upgraded'].includes(d[e])) {
            return d[e];
        }
    }
    return '';
}

const unix_time = () => {
    return Math.floor(new Date().getTime() / 1000);
}

export function itemTip(a) {
    // console.log('item tip init data', a)
    const b = ["", "", "", "", "", "", "", "", "", ""];
    if ("undefined" == typeof c) var c = {};
    isset(a.name) && (b[0] = "<b>" + htmlspecialchars(a.name) + "</b>"),
    isset(a.cl) &&
    (b[1] =
        25 == a.cl && 10 != parseInt(a.st)
            ? _t("itype %type% %value%", {
            "%type%": _t("type"),
            "%value%": _t("cl_mixtures"),
        }) + "<br />"
            : _t("itype %type% %value%", {
            "%type%": _t("type"),
            "%value%": eq.classes[a.cl],
        }) + "<br />");
    const d = a.stat.split(";");
    for (var e in d)
        if ("string" == typeof d[e]) {
            var f,
                g = d[e].split("=");
            switch (g[0]) {
                case "name":
                    (b[0] = "<b>" + htmlspecialchars(g[1]) + "</b>"),
                        (b[0] += "<i>" + htmlspecialchars(a.name) + "</i>");
                    break;
                case "unique":
                    b[0] += "<b class=unique>* " + _t("type_unique") + " *</b>";
                    break;
                case "heroic":
                    b[0] += "<b class=heroic>* " + _t("type_heroic") + " *</b>";
                    break;
                case "legendary":
                    b[0] += "<b class=legendary>* " + _t("type_legendary") + " *</b>";
                    break;
                case "artefact":
                    b[0] += "<b class=artefact>* " + _t("type_artifact") + " *</b>";
                    break;
                case "upgraded":
                    b[0] += "<b class=upgraded>* " + _t("type_modified") + " *</b>";
                    break;
                case "upg":
                    var h = parseItemStat(a.stat);
                    (b[0] +=
                        "<b class=upgraded>* " +
                        _t("type_modification %val%", {
                            "%val%": g[1],
                        }) +
                        " *</b>"),
                    isset(h.upgby) &&
                    (b[7] +=
                        '<span style="color:yellow">' +
                        _t("type_modification_upgb %val% %name%", {
                            "%val%": g[1],
                            "%name%": h.upgby,
                        }) +
                        "</span><br />");
                    break;
                case "improve":
                    var i = g[1].split(","),
                        j = 1;
                    switch (i[0]) {
                        case "armor":
                        case "jewels":
                            j = 1.3;
                            break;
                        case "armorb":
                        case "weapon":
                            j = 1;
                    }
                    (b[3] +=
                        _t("improves %items%", {
                            "%items%": _t("improve_" + i[0]),
                        }) + "<br />"),
                        (b[3] +=
                            _t("types_list %upg_normal% %upg_uni% %upg_hero%", {
                                "%upg_normal%": Math.round(j * i[1]),
                                "%upg_uni%": Math.round(j * i[1] * 0.7),
                                "%upg_hero%": Math.round(j * i[1] * 0.4),
                            }) + "<br />"),
                        (b[3] += _t("improve_item_bound_info") + "<br /><br />");
                    break;
                case "upgby":
                    break;
                case "lowreq":
                    b[0] +=
                        "<b class=upgraded>* " +
                        _t("type_lower_req %val%", {
                            "%val%": g[1],
                        }) +
                        " *</b>";
                    break;
                case "ac":
                    b[1] +=
                        _t("item_ac %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "act":
                    b[1] +=
                        _t("item_act %val%", {
                            "%val%": mp(g[1]),
                        }) + "<br>";
                    break;
                case "resfire":
                    b[1] +=
                        _t("item_resfire %val%", {
                            "%val%": mp(g[1]),
                        }) + "<br>";
                    break;
                case "reslight":
                    b[1] +=
                        _t("item_reslight %val%", {
                            "%val%": mp(g[1]),
                        }) + "<br>";
                    break;
                case "resfrost":
                    b[1] +=
                        _t("item_resfrost %val%", {
                            "%val%": mp(g[1]),
                        }) + "<br>";
                    break;
                case "dmg":
                    b[1] +=
                        _t("item_dmg %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "pdmg":
                    b[1] +=
                        _t("item_pdmg %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "perdmg":
                    b[1] +=
                        _t("item_perdmg %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "zr":
                    b[2] +=
                        _t("item_zr %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "sila":
                    b[2] +=
                        _t("item_sila %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "int":
                    b[2] += _t("item_int %val%") + "<br>";
                    break;
                case "str":
                    b[2] +=
                        _t("skill_str %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "of-str":
                    b[2] +=
                        _t("skill_of-str %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "agi":
                    b[2] +=
                        _t("skills_agi %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "firebon":
                    b[2] +=
                        _t("skills_firebon %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "lightbon":
                    b[2] +=
                        _t("skills_lightbon %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "frostbon":
                    b[2] +=
                        _t("skills_frostbon %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "critslow":
                    b[3] +=
                        _t("skills_critslow %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "critsa":
                    b[3] +=
                        _t("skills_critsa %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "lastcrit":
                    b[3] +=
                        _t("skills_lastcrit %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "decevade":
                    b[3] +=
                        _t("skills_decevade %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "redslow":
                    b[3] +=
                        _t("skills_redslow %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "woundred":
                    b[3] +=
                        _t("skills_woundred %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "healpower":
                    b[3] +=
                        _t("skills_healpower %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "engback":
                    b[3] +=
                        _t("skills_engback %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "sa-clothes":
                    b[3] +=
                        _t("skills_sa-clothes %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "red-sa":
                    b[3] +=
                        _t("skills_red-sa %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "footshoot":
                    b[3] +=
                        _t("skills_footshoot %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "critwound":
                    b[3] +=
                        _t("skills_critwound %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "swing":
                    b[3] +=
                        _t("skills_swing %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "distract":
                    b[3] +=
                        _t("skills_distract %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "injure":
                    b[3] +=
                        _t("skills_injure %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "rage":
                    b[3] +=
                        _t("skills_rage %val% %turn%", {
                            "%val%": g[1],
                            "%turn%": _t(parseInt(g[1]) > 1 ? "turns" : "turn"),
                        }) + "<br>";
                    break;
                case "reusearrows":
                    b[3] +=
                        _t("skills_reusearrows %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "pcontra":
                    b[3] +=
                        _t("skills_pcontra %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "fastarrow":
                    b[3] +=
                        _t("skills_fastarrow %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "bandage":
                    b[3] +=
                        _t("skills_bandage %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "set":
                    b[3] +=
                        _t("skills_set %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "fireshield":
                    b[3] +=
                        _t("skills_fireshield %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "frostshield":
                    b[3] +=
                        _t("skills_frostshield %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "lightshield":
                    b[3] +=
                        _t("skills_lightshield %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "longfireshield":
                    b[3] +=
                        _t("skills_longfireshield %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "longfrostshield":
                    b[3] +=
                        _t("skills_longfrostshield %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "longlightshield":
                    b[3] +=
                        _t("skills_longlightshield %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "soullink":
                    b[3] +=
                        _t("skills_soullink %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "poisonbon":
                    b[3] +=
                        _t("skills_poisonbon %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "of-thirdatt":
                    b[3] +=
                        _t("skills_of-thirdatt %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "woundchance":
                    b[3] +=
                        _t("skills_woundchance %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "wounddmgbon":
                    b[3] +=
                        _t("skills_wounddmgbon %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "arrowrain":
                    b[3] +=
                        _t("skills_arrowrain %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "doubleshoot":
                    break;
                case "disturb":
                    b[3] +=
                        _t("skills_disturb %val%", {
                            "%val%": g[1],
                            "%val2%": 2 * parseInt(g[1]),
                        }) + "<br>";
                    break;
                case "shout":
                    b[3] +=
                        _t("skills_shout %val%", {
                            "%val%":
                                g[1] > 1
                                    ? _t("enemies %amount%", {
                                        "%amount%": g[1],
                                    })
                                    : _t("oneenemy"),
                        }) + "<br>";
                    break;
                case "insult":
                    b[3] +=
                        _t("skills_insult %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "frostpunch":
                    b[2] +=
                        _t("skills_frostpunch %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "redstun":
                    b[3] +=
                        _t("skills_redstun %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "lightmindmg":
                    b[2] +=
                        _t("skills_lightmindmg %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "actdmg":
                    b[2] +=
                        _t("skills_actdmg %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "hpsa":
                    b[2] +=
                        _t("skills_hpsa %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "mresdmg":
                    b[2] +=
                        _t("skills_hpsa %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "reqw":
                    b[9] += '<b class="att">' + _t("skills_req_weapon");
                    var k = g[1].split(",");
                    for (var e in k)
                        b[9] +=
                            (isset(eq.weapon[k[e]]) ? eq.weapon[k[e]] : "???") +
                            (isset(k[parseInt(e) + 1]) ? ", " : "");
                    b[9] += "</b><br>";
                    break;
                case "rskl":
                    isset(a.tmpSkills) &&
                    isset(a.tmpSkills.names) &&
                    ((f = g[1].split("-")),
                        (b[9] += isset(a.tmpSkills.names[f[0]])
                            ? '<b class="att' +
                            (f[1] > a.tmpSkills.names[f[0]].l ? " noreq" : "") +
                            '">' +
                            _t("skills_req_skill") +
                            "<br>&nbsp;&nbsp;&nbsp;" +
                            (isset(a.tmpSkills.names[f[0]])
                                ? a.tmpSkills.names[f[0]].n
                                : "???") +
                            " (" +
                            f[1] +
                            ")</b><br>"
                            : '<b class="att noreq">error</b>'));
                    break;
                case "hp":
                    b[3] +=
                        _t("bonus_hp %val%", {
                            "%val%": mp(g[1]),
                        }) + "<br>";
                    break;
                case "sa1":
                case "sa":
                    b[3] +=
                        _t("bonus_sa %val%", {
                            "%val%": mp(g[1]),
                        }) + "<br>";
                    break;
                case "ds":
                    b[3] +=
                        _t("bonus_ds %val%", {
                            "%val%": mp(g[1]),
                        }) + "<br>";
                    break;
                case "dz":
                    b[3] +=
                        _t("bonus_dz %val%", {
                            "%val%": mp(g[1]),
                        }) + "<br>";
                    break;
                case "di":
                    b[3] +=
                        _t("bonus_di %val%", {
                            "%val%": mp(g[1]),
                        }) + "<br>";
                    break;
                case "da":
                    b[3] +=
                        _t("bonus_da %val%", {
                            "%val%": mp(g[1]),
                        }) + "<br>";
                    break;
                case "gold":
                    b[3] +=
                        _t("bonus_gold %val%", {
                            "%val%": mp(g[1]),
                        }) + "<br>";
                    break;
                case "creditsbon":
                    b[3] += _t("bonus_creditsbon") + "<br>";
                    break;
                case "runes":
                    b[3] +=
                        _t("bonus_runes %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "goldpack":
                    b[3] +=
                        _t("bonus_goldpack %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "leczy":
                    b[3] +=
                        g[1] > 0
                            ? _t("bonus_leczy %val%", {
                            "%val%": g[1],
                        }) + "<br>"
                            : _t("bonus_truje %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "fullheal":
                    b[3] +=
                        _t("bonus_fullheal %val%", {
                            "%val%": round(g[1], 2),
                        }) + "<br>";
                    break;
                case "perheal":
                    b[3] +=
                        _t("bonus_perheal %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "blok":
                    b[3] +=
                        _t("bonus_blok %val%", {
                            "%val%": mp(g[1]),
                        }) + "<br>";
                    break;
                case "crit":
                    b[3] +=
                        _t("bonus_crit %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "of-crit":
                    b[3] +=
                        _t("bonus_of-crit %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "critval":
                    b[3] +=
                        _t("bonus_critval %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "of-critval":
                    b[3] +=
                        _t("bonus_of-critval %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "critmval":
                    b[3] +=
                        _t("bonus_of-critmval %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "critmval_f":
                    b[3] +=
                        _t("bonus_critmval_f %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "critmval_c":
                    b[3] +=
                        _t("bonus_critmval_c %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "critmval_l":
                    b[3] +=
                        _t("bonus_critmval_l %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "heal":
                    b[3] +=
                        _t("bonus_heal %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "evade":
                    b[3] +=
                        _t("bonus_evade %val%", {
                            "%val%": mp(g[1]),
                        }) + "<br>";
                    break;
                case "pierce":
                    b[3] +=
                        _t("bonus_pierce %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "pierceb":
                    b[3] +=
                        _t("bonus_pierceb %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "contra":
                    b[3] +=
                        _t("bonus_contra %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "parry":
                    b[3] +=
                        _t("bonus_parry %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "revive":
                    b[3] +=
                        _t("revive %amount%", {
                            "%amount%": g[1],
                        }) + "<br>";
                    break;
                case "frost":
                    (f = g[1].split(",")),
                        (b[2] +=
                            _t("bonus_frost %val% %slow%", {
                                "%val%": f[1],
                                "%slow%": f[0] / 100,
                            }) + "<br>");
                    break;
                case "poison":
                    (f = g[1].split(",")),
                        (b[2] +=
                            _t("bonus_poison %val% %slow%", {
                                "%val%": f[1],
                                "%slow%": f[0] / 100,
                            }) + "<br>");
                    break;
                case "slow":
                    b[3] +=
                        _t("bonus_slow %val%", {
                            "%val%": g[1] / 100,
                        }) + "<br>";
                    break;
                case "wound":
                    (f = g[1].split(",")),
                        (b[3] +=
                            _t("bonus_wound %val% %dmg%", {
                                "%val%": f[0],
                                "%dmg%": f[1],
                            }) + "<br>");
                    break;
                case "fire":
                    b[2] +=
                        _t("bonus_fire %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "light":
                    b[2] +=
                        _t("bonus_light %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "adest":
                    b[3] +=
                        _t("bonus_adest %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "absorb":
                    b[3] +=
                        _t("bonus_absorb %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "absorbm":
                    b[3] +=
                        _t("bonus_absorbm %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "hpbon":
                    b[3] +=
                        _t("bonus_hpbon %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "acdmg":
                    b[3] +=
                        _t("bonus_acdmg %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "resdmg":
                    b[3] +=
                        _t("bonus_resdmg %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "energy":
                    b[3] +=
                        g[1] > 0
                            ? _t("bonus_energy1 %val%", {
                            "%val%": g[1],
                        }) + "<br>"
                            : _t("bonus_energy2 %val%", {
                            "%val%": Math.abs(g[1]),
                        }) + "<br>";
                    break;
                case "energybon":
                    b[3] +=
                        _t("bonus_energybon %val%", {
                            "%val%": mp(g[1]),
                        }) + "<br>";
                    break;
                case "energygain":
                    b[3] +=
                        _t("bonus_energygain %val%", {
                            "%val%": mp(g[1]),
                        }) + "<br>";
                    break;
                case "en-regen":
                    b[3] +=
                        _t("bonus_en-regen %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "energyp":
                    b[3] +=
                        g[1] > 0
                            ? _t("bonus_energyp1 %val%", {
                            "%val%": mp(g[1]),
                        }) + "<br>"
                            : _t("bonus_energyp2 %val%", {
                            "%val%": Math.abs(g[1]),
                        }) + "<br>";
                    break;
                case "mana":
                    b[3] +=
                        g[1] > 0
                            ? _t("bonus_mana1 %val%", {
                            "%val%": g[1],
                        }) + "<br>"
                            : _t("bonus_mana2 %val%", {
                            "%val%": Math.abs(g[1]),
                        }) + "<br>";
                    break;
                case "manabon":
                    b[3] +=
                        _t("bonus_manabon %val%", {
                            "%val%": mp(g[1]),
                        }) + "<br>";
                    break;
                case "managain":
                    b[3] +=
                        _t("bonus_managain %val%", {
                            "%val%": mp(g[1]),
                        }) + "<br>";
                    break;
                case "manastr":
                    b[3] +=
                        _t("bonus_manastr %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "manarestore":
                    b[3] +=
                        _t("bonus_manarestore %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "manatransfer":
                    b[3] +=
                        _t("bonus_manatransfer %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "stun":
                    b[3] +=
                        _t("bonus_stun %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "freeze":
                    b[3] +=
                        _t("bonus_freeze %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "hpcost":
                    b[3] +=
                        _t("bonus_hpcost %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "cover":
                    b[3] +=
                        _t("bonus_cover %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "allslow":
                    b[3] +=
                        _t("bonus_allslow %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "firearrow":
                case "firepunch":
                case "firebolt":
                    b[3] +=
                        _t("bonus_firebolt %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "firewall":
                    b[3] +=
                        _t("bonus_firewall %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "thunder":
                    b[3] +=
                        _t("bonus_thunder %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "storm":
                    b[3] +=
                        _t("bonus_storm %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "lowdmg":
                    b[3] +=
                        _t("bonus_lowdmg %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "blizzard":
                    b[3] +=
                        _t("bonus_blizzard %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "sunshield":
                    b[3] +=
                        _t("bonus_sunshield %val%", {
                            "%val%": g[1],
                            "%val2%": g[1] / 2,
                        }) + "<br>";
                    break;
                case "sunreduction":
                    b[3] +=
                        _t("bonus_sunreduction %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "healall":
                    b[3] +=
                        _t("bonus_healall %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "heal1":
                    b[3] +=
                        _t("bonus_heal1 %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "aura-ac":
                    b[3] +=
                        _t("bonus_aura-ac %val%", {
                            "%val%": mp(g[1]),
                        }) + "<br>";
                    break;
                case "aura-resall":
                    b[3] +=
                        _t("bonus_aura-resall %val%", {
                            "%val%": mp(g[1]),
                        }) + "<br>";
                    break;
                case "aura-sa":
                    b[3] +=
                        _t("bonus_aura-sa %val%", {
                            "%val%": mp(g[1] / 100),
                        }) + "<br>";
                    break;
                case "absorbd":
                    b[3] +=
                        _t("bonus_absorbd %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "stinkbomb":
                    b[3] +=
                        _t("bonus_stinkbomb %val% %crit%", {
                            "%val%": 2 * parseInt(g[1]),
                            "%crit%": g[1],
                        }) + "<br>";
                    break;
                case "abdest":
                    b[3] +=
                        _t("bonus_abdest %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "endest":
                    b[3] +=
                        _t("bonus_endest %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "manadest":
                    b[3] +=
                        _t("bonus_manadest %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "lowevade":
                    b[3] +=
                        _t("bonus_lowevade %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "lowcrit":
                    b[3] +=
                        _t("bonus_lowcrit %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "arrowblock":
                    b[3] +=
                        _t("bonus_arrowblock %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "bag":
                    var l =
                        "pl" == _l()
                            ? ![2, 3, 4].includes(g[1] % 10) || (g[1] >= 6 && g[1] <= 19)
                                ? "ów"
                                : "y"
                            : g[1] > 1
                                ? "s"
                                : "";
                    b[3] +=
                        _t("bonus_bag %val%", {
                            "%val%": g[1],
                            "%posfix%": l,
                        }) + "<br>";
                    break;
                case "pkey":
                    b[3] += _t("bonus_pkey") + "<br>";
                    break;
                case "btype":
                    b[4] +=
                        _t("bonus_btype %val%", {
                            "%val%": eq.classes[g[1]].toLowerCase(),
                        }) + "<br>";
                    break;
                case "respred":
                    b[3] +=
                        _t("bonus_respred %val%", {
                            "%val%": g[1],
                        }) + "<br>";
                    break;
                case "afterheal":
                case "afterheal2":
                    var f = g[1].split(",");
                    b[3] +=
                        f[0] +
                        _t("bonus_afterheal2 %val%", {
                            "%val%": f[1],
                        }) +
                        "<br>";
                    break;
                case "action":
                    var m = g[1].split(",");
                    switch (m[0]) {
                        case "flee":
                            b[3] += _t("flee_item_description") + "<br />";
                            break;
                        case "mail":
                            b[3] += _t("mail_item_description") + "<br />";
                            break;
                        case "auction":
                            b[3] += _t("auction_item_description") + "<br />";
                            break;
                        case "nloc":
                            b[3] +=
                                "*" == m[1]
                                    ? _t("nloc_heros_item_description") + "<br />"
                                    : _t("nloc_monster_item_description") + "<br />";
                            break;
                        case "fatigue":
                            var n = parseInt(m[1]);
                            b[3] +=
                                n > 0
                                    ? _t("fatigue_positive %val%", {
                                    "%val%": Math.abs(n),
                                }) + "<br />"
                                    : _t("fatigue_negative %val%", {
                                    "%val%": Math.abs(n),
                                }) + "<br />";
                    }
                    break;
                case "outfit":
                    var f = g[1].split(","),
                        o = "";
                    (o =
                        f[0] < 1
                            ? _t("outfit_perm")
                            : 1 == f[0]
                                ? _t("outfit_1min")
                                : f[0] < 5
                                    ? f[0] + _t("outfit_mins1")
                                    : f[0] < 99
                                        ? f[0] + _t("outfit_mins2")
                                        : f[0] < 300
                                            ? round(f[0] / 60) + _t("outfit_hrs1")
                                            : round(f[0] / 60) + _t("outfit_hrs2")),
                        (b[3] +=
                            _t("outfit_change_for %time%", {
                                "%time%": o,
                            }) + "<br>");
                    break;
                case "timelimit":
                    var f = g[1].split(",");
                    if (
                        ((b[3] +=
                            f[0] < 1
                                ? _t("timelimit_can be used %val% sec", {
                                "%val%": f[0],
                            }) + "<br>"
                                : 1 == f[0]
                                    ? _t("timelimit_can be used every min") + "<br>"
                                    : f[0] < 5
                                        ? _t("timelimit_can be used %val% minutes", {
                                        "%val%": f[0],
                                    }) + "<br>"
                                        : _t("timelimit_can be used %val% minutes2", {
                                        "%val%": f[0],
                                    }) + "<br>"),
                            isset(f[1]))
                    ) {
                        const p = Math.floor((parseInt(f[1]) - unix_time()) / 60);
                        b[3] +=
                            0 > p
                                ? _t("timelimit_readyToUse_now") + "<br>"
                                : 1 > p
                                    ? _t("timelimit_readyToUse_inawhile") + "<br>"
                                    : 1 == p
                                        ? _t("timelimit_readyToUse_inaminute") + "<br>"
                                        : 5 > p
                                            ? _t("timelimit_readyToUse_in %val% sec", {
                                            "%val%": p,
                                        }) + "<br>"
                                            : _t("timelimit_readyToUse_in %val% min", {
                                            "%val%": p,
                                        }) + "<br>";
                    }
                    break;
                case "expires":
                    b[3] +=
                        g[1] - unix_time() < 259200
                            ? "<b class=expires>" +
                            _t("valid_to %date%", {
                                "%val%": ut_date(g[1]),
                            }) +
                            "</b>"
                            : _t("valid_to %date%", {
                                "%val%": ut_date(g[1]),
                            });
                    break;
                case "ttl":
                    b[4] +=
                        25 == a.cl &&
                        ("t" == a.loc || "n" == a.loc || ("g" == a.loc && 0 == a.st))
                            ? _t("ttl1 %date%", {
                            "%val%": g[1],
                        }) + "<br />"
                            : _t("ttl2 %date%", {
                            "%val%": g[1],
                        }) + "<br />";
                    break;
                case "ammo":
                case "amount":
                    var q = parseItemStat(a.stat),
                        r = parseInt("ammo" == g[0] ? q.ammo : q.amount),
                        s = !0;
                    1 >= r && (s = !1),
                    isset(q.quest) && (s = !1),
                    isset(q.upgraded) && (s = !1),
                    isset(q.capacity) || 21 == a.cl || (s = !1);
                    var t = _t(s ? "split_possible" : "split_impossible");
                case "amount":
                    10 != a.st &&
                    (b[4] +=
                        _t("amount %val% %split%", {
                            "%val%": g[1],
                            "%split%": t,
                        }) + "<br>");
                    break;
                case "capacity":
                    10 != a.st &&
                    (b[4] +=
                        _t("capacity %val%", {
                            "%val%": g[1],
                        }) + "<br>");
                    break;
                case "ammo":
                    b[4] +=
                        _t("ammo %val% %split%", {
                            "%val%": g[1],
                            "%split%": t,
                        }) +
                        "<br" +
                        (g[1] < 50 ? " noammo" : "") +
                        ">";
                    break;
                case "nodepo":
                    b[4] += _t("nodepo_info") + "<br />";
                    break;
                case "legbon":
                    b[5] += "<i class=legbon>";
                    var f = g[1].split(",");
                    switch (f[0]) {
                        case "verycrit":
                            b[5] += _t("legbon_verycrit");
                            break;
                        case "holytouch":
                            b[5] += _t("legbon_holytouch");
                            break;
                        case "curse":
                            b[5] += _t("legbon_curse");
                            break;
                        case "pushback":
                            b[5] += _t("legbon_pushback");
                            break;
                        case "lastheal":
                            b[5] += _t("legbon_lastheal");
                            break;
                        case "critred":
                            b[5] += _t("legbon_critred");
                            break;
                        case "resgain":
                            b[5] += _t("legbon_resgain");
                            break;
                        case "dmgred":
                            b[5] += _t("legbon_dmgred");
                            break;
                        default:
                            b[5] += _t("legbon_undefined %val%", {
                                "%val%": f[0],
                            });
                    }
                    b[5] += "</i>";
                    break;
                case "teleport":
                    b[6] += "<i class=idesc>" + _t("teleport") + "</i>";
                    break;
                case "townlimit":
                    b[6] += _t("townlimit") + "<br>";
                    break;
                case "furniture":
                    b[6] += _t("furniture", null, "itemtip") + "<br>";
                    break;
                case "nodesc":
                    b[6] += "<i class=idesc>" + _t("nodesc") + "</i>";
                    break;
                case "created":
                    break;
                case "opis":
                    var q = parseItemStat(a.stat);
                    (g[1] = g[1].replace(/#DATE#|#YEAR#/g, function (b) {
                        switch (b) {
                            case "#DATE#":
                                return ut_date(isset(q.created) ? q.created : unix_time());
                            case "#YEAR#":
                                if (!isset(q.created)) {
                                    if ("n" == a.loc || "v" == a.loc) {
                                        const c = new Date();
                                        return c.getFullYear();
                                    }
                                    return "2012";
                                }
                                return ut_date(q.created).substr(-4);
                        }
                    })),
                        (b[7] += "<i class=idesc>" + htmlspecialchars(g[1]) + "</i>");
                    break;
                case "loot":
                    var f = g[1].split(","),
                        u = "";
                    2 == f[2] && (u = _t("with_player")),
                    f[2] > 2 && (u = _t("with_company")),
                        (b[7] +=
                            "<i class=looter>" +
                            htmlspecialchars(
                                _t("loot_with %day% %npc% %grpinf% %name%", {
                                    "%day%": ut_date(f[3]),
                                    "%npc%": f[4],
                                    "%grpinf%": u,
                                    "%name%": f[0],
                                })
                            ) +
                            "</i><br>");
                    break;
                case "soulbound":
                    b[8] +=
                        "n" == a.loc
                            ? _t("soulbound") + "<br>"
                            : 22 == a.cl
                                ? _t("soulbound1") + "<br>"
                                : _t("soulbound2") + "<br>";
                    break;
                case "recovered":
                    b[8] += _t("recovered") + "<br>";
                    break;
                case "binds":
                    b[8] += 22 == a.cl ? _t("binds1") + "<br>" : _t("binds2") + "<br>";
                    break;
                case "unbind":
                    b[8] += _t("unbind") + "<br>";
                    break;
                case "undoupg":
                    b[8] += _t("undoupg") + "<br>";
                    break;
                case "notakeoff":
                    b[8] = _t("notakeoff") + "<br>";
                    break;
                case "lvl":
                    b[9] +=
                        '<b class="att' +
                        (g[1] > hero.lvl ? " noreq" : "") +
                        '">' +
                        _t("lvl %lvl%", {
                            "%lvl%": g[1],
                        }) +
                        "</b><br>";
                    break;
                case "reqp":
                    b[9] +=
                        '<b class="att' +
                        (g[1].indexOf(hero.prof) < 0 ? " noreq" : "") +
                        '">' +
                        _t("reqp") +
                        " ";
                    for (var v = 0; v < g[1].length; v++)
                        b[9] += (v ? ", " : "") + eq.prof[g[1].charAt(v)];
                    b[9] += "</b><br>";
                    break;
                case "reqgold":
                    b[9] +=
                        '<b class="att' +
                        (g[1] > hero.gold ? " noreq" : "") +
                        '">' +
                        _t("reqgold %val%", {
                            "%val%": g[1],
                        }) +
                        "</b><br>";
                    break;
                case "reqs":
                    b[9] +=
                        '<b class="att' +
                        (g[1] > hero.bstr ? " noreq" : "") +
                        '">' +
                        _t("reqs %val%", {
                            "%val%": g[1],
                        }) +
                        "</b><br>";
                    break;
                case "reqz":
                    b[9] +=
                        '<b class="att' +
                        (g[1] > hero.bagi ? " noreq" : "") +
                        '">' +
                        _t("reqz %val%", {
                            "%val%": g[1],
                        }) +
                        "</b><br>";
                    break;
                case "reqi":
                    b[9] +=
                        '<b class="att' +
                        (g[1] > hero.bint ? " noreq" : "") +
                        '">' +
                        _t("reqi %val%", {
                            "%val%": g[1],
                        }) +
                        "</b><br>";
                    break;
                case "pet":
                    g[1].match(/elite/) &&
                    (b[0] +=
                        '<i style="color:yellow">' +
                        _t("pet_elite", null, "pet_tip") +
                        "</i>"),
                    g[1].match(/heroic/) &&
                    (b[0] +=
                        '<i style="color:#2090FE">' +
                        _t("pet_heroic", null, "pet_tip") +
                        "</i>"),
                    g[1].match(/legendary/) &&
                    (b[0] +=
                        '<i style="color:#FA9A20;">' +
                        _t("pet_legendary", null, "pet_tip") +
                        "</i>");
                    for (var w = g[1].split(","), v = 2; v < w.length; v++)
                        if ("elite" != w[v] && "quest" != w[v]) {
                            const x = w[v].split("|");
                            if (x.length) {
                                b[2] = '<span style="color:lime">' + _t("pet_tasks") + "<br />";
                                for (var e = 0; e < x.length; e++)
                                    b[2] += "-" + x[e].replace(/#.*/, "") + "<br />";
                                b[2] += "</span>";
                            }
                            break;
                        }
                    g[1].match(/quest/) && (b[2] += _t("pet_logout_hide") + "<br />");
                    break;
                case "outexchange":
                    b[8] += _t("outexchange") + "<br>";
                    break;
                case "book":
                case "price":
                case "resp":
                case "key":
                case "keym":
                case "rkey":
                case "rlvl":
                case "noauction":
                case "motel":
                case "emo":
                case "quest":
                case "play":
                case "szablon":
                case "null":
                case "progress":
                    break;
                default:
                    "" != g[0] &&
                    (b[3] +=
                        _t("unknown_stat %val%", {
                            "%val%": g[0],
                        }) + "<br>");
            }
        }
    return (
        a.pr &&
        (!isset(a.cl) ||
            25 != a.cl ||
            "n" == a.loc ||
            ("t" == a.loc && isset(a.cl) && 25 == a.cl))
            ? (b[9] += _t("item_value %val%", {
                "%val%": round(a.pr, 2),
            }))
            : (b[9] = b[9].replace(/\<br>$/g, "")),
            b.join("")
    );
}
