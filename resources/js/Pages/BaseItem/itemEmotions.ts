/**
 * Emotions an item can show (attribute `useEmotion`), same list as virtigia-tips ITEM_EMOTIONS and the engine's
 * ItemEmotion enum. Thrown ("targeted") emotions are used by dragging the item onto another player.
 */
export interface ItemEmotionOption {
    value: string;
    label: string;
    targeted: boolean;
}

const self = (value: string, label: string): ItemEmotionOption => ({value, label, targeted: false});
const thrown = (value: string, label: string): ItemEmotionOption => ({value, label, targeted: true});

export const ITEM_EMOTION_OPTIONS: ItemEmotionOption[] = [
    self('banki', 'Bańki mydlane'),
    self('curse', 'Klątwa'),
    self('emonutki', 'Nutki'),
    self('guma1', 'Balon z gumy (truskawkowy)'),
    self('guma2', 'Balon z gumy (jagodowy)'),
    self('guma3', 'Balon z gumy (malinowy)'),
    self('guma4', 'Balon z gumy (jabłkowy)'),
    self('kuciak', 'Kurczaczek'),
    self('ptaszek', 'Papuga'),
    self('stars', 'Gwiazdki'),
    self('termofor', 'Termofor'),
    self('emosnow', 'Śnieżyca'),
    self('ghost', 'Duch'),
    self('water', 'Śmingus-dyngus'),
    self('zombie', 'Zombie'),
    self('bat', 'Nietoperz'),
    self('spider', 'Pająk'),
    self('angry', 'Złość'),
    self('fire_1', 'Sztuczne ognie (fioletowe)'),
    self('fire_2', 'Sztuczne ognie (błękitne)'),
    self('fire_3', 'Sztuczne ognie (czerwone)'),
    self('fire_4', 'Sztuczne ognie (różowe)'),
    self('fire_5', 'Sztuczne ognie (zielone)'),
    self('fire_6', 'Sztuczne ognie (niebieskie)'),
    self('fire_7', 'Sztuczne ognie (białe)'),
    self('fire_8', 'Ognista ósemka'),
    self('fire_9', 'Czaszka z dymu'),
    self('fireworks', 'Fajerwerki'),
    self('lampion1', 'Lampion'),
    self('lampion2', 'Lampion (czerwony)'),
    self('lampion3', 'Lampion'),
    self('lampion4', 'Lampion (zielony)'),
    self('lampion5', 'Lampion'),
    self('flag-anglia.gif', 'Flaga Anglii'),
    self('flag-chorwacja.gif', 'Flaga Chorwacji'),
    self('flag-czechy.gif', 'Flaga Czech'),
    self('flag-dania.gif', 'Flaga Danii'),
    self('flag-francja.gif', 'Flaga Francji'),
    self('flag-grecja.gif', 'Flaga Grecji'),
    self('flag-hiszpania.gif', 'Flaga Hiszpanii'),
    self('flag-holandia.gif', 'Flaga Holandii'),
    self('flag-irlandia.gif', 'Flaga Irlandii'),
    self('flag-niemcy.gif', 'Flaga Niemiec'),
    self('flag-polska.gif', 'Flaga Polski'),
    self('flag-portugalia.gif', 'Flaga Portugalii'),
    self('flag-rosja.gif', 'Flaga Rosji'),
    self('flag-skull.gif', 'Piracka bandera'),
    self('flag-szwecja.gif', 'Flaga Szwecji'),
    self('flag-ukraina.gif', 'Flaga Ukrainy'),
    self('flag-wlochy.gif', 'Flaga Włoch'),
    thrown('buzia1', 'Buziak'),
    thrown('buzia2', 'Buzia świnki'),
    thrown('roza', 'Róża'),
    thrown('cocos', 'Kokos'),
    thrown('emohug1', 'Świąteczny uścisk'),
    thrown('emohug2', 'Mocny świąteczny uścisk'),
    thrown('emohug3', 'Uścisk'),
    thrown('gift', 'Prezent'),
    thrown('holly', 'Jemioła'),
    thrown('snowball', 'Śnieżka'),
    thrown('pillow', 'Bitwa na poduszki'),
    thrown('kiss', 'Pocałunek'),
    thrown('kiss2', 'Serdeczne myśli'),
];

export const ITEM_EMOTION_SELECT_OPTIONS = ITEM_EMOTION_OPTIONS.map(option => ({
    ...option,
    label: `${option.label}${option.targeted ? ' — rzucana na gracza' : ''} (${option.value})`,
}));
