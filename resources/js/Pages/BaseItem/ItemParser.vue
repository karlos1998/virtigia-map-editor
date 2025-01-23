<script setup lang="ts">

import { watch } from "vue";
const statPositive = (value: string | number) => `+${statNumber(value)}`;
const statPercent = (value: string | number) => `${statSigner(statNumber(value))}%`;
const statSigner = (value: string | number) => Math.sign(statNumber(value)) === 1 ? statPositive(value) : `${statNumber(value)}`;
const statNumber = (value: string | number) => Number(value);
const statBox = (value: string | number) => `<span role="value${itemProperties.config.colorizeAttributes ? '-colorized' : ''}">${value}</span>`;

function parsePrice(value: number) {
    if(isNaN(value)) {
        throw new Error("Input must be a valid integer");
    };
    const thresholds = [1e9, 1e6, 1e3];
    const suffixes = ["g", "m", "k"];

    for (let i = 0; i < thresholds.length; i++) {
        if (value >= thresholds[i]) {
            return (value / thresholds[i]).toFixed(2).replace(/\.00$/, "") + suffixes[i];
        };
    };

    return value.toString();
};

const translations = {
    "categories": {
        "consumable": "Konsumpcyjne",
        "halfHanded": "Półtoraręczne",
        "twoHanded": "Dwuręczne",
        "oneHanded": "Jednoręczne",
        "blessings": "Błogosławieństwa",
        "auxiliary": "Pomocnicze",
        "renewable": "Odnawialne",
        "distances": "Dystansowe",
        "necklaces": "Naszyjniki",
        "talismans": "Talizmany",
        "neutrals": "Neutralne",
        "upgrades": "Ulepszenia",
        "shields": "Tarcze",
        "helmets": "Hełmy",
        "gloves": "Rękawice",
        "armors": "Zbroje",
        "arrows": "Strzały",
        "quests": "Questowe",
        "rings": "Pierścienie",
        "boots": "Buty",
        "wands": "Różdżki",
        "golds": "Złoto",
        "books": "Książki",
        "orbs": "Orby magiczne",
        "keys": "Klucze",
        "bags": "Torby",
    },
    "professions": {
        "b": "Tancerz ostrzy",
        "t": "Tropiciel",
        "w": "Wojownik",
        "p": "Paladyn",
        "h": "Łowca",
        "m": "Mag"
    },
    "attributes": {
        "requirements": {
            "needProfessions": (professionList: string[]) => {
                return `Wymagane profesje: ${professionList.map((professionLetter: keyof typeof translations.professions) => translations.professions[professionLetter]).join(", ")}`;
            },
            "needIntellect": (requirementData: string) => {
                return `Wymagany intelekt: ${statNumber(requirementData)}`;
            },
            "needStrength": (requirementData: string) => {
                return `Wymagana siła: ${statNumber(requirementData)}`;
            },
            "needAgility": (requirementData: string) => {
                return `Wymagana zręczność: ${statNumber(requirementData)}`;
            },
            "needLevel": (requirementData: string) => {
                return `Wymagany poziom: ${statNumber(requirementData)}`;
            },
        },
        "bonuses": {
            "criticalReductionDuringDefending": (percentData: string) => {
                return `Podczas obrony szansa na cios krytyczny przeciwnika jest mniejsza o ${statBox(statPercent(Number(percentData)))}`;
            } ,
            "manaEnergyDestroyReduction": (pointsData: string) => {
                const manaPoints = Number(pointsData);
                const energyPoints = Math.max(1, Math.round(0.444 * manaPoints));

                return `Obniżanie niszczenia many o ${statBox(statNumber(manaPoints))} punktów oraz energii o ${statBox(statNumber(energyPoints))}`;
            },
            "enemyAttackSpeedReduction": (pointsData: string) => {
                return `Obniża szybkość ataku przeciwnika o ${statBox(statNumber(pointsData) / 100)}`;
            },
            "healthRestorationPercent": (percentData: string) => {
                return `Odnawia ${statBox(statPercent(statNumber(percentData)))} punktów życia`;
            },
            "combatHealthRestoration": (pointsData: string) => {
                return `Przywraca ${statBox(pointsData)} punktów życia podczas walki`;
            },
            "addEnchancementPoints": (percentData: string) => {
                return `Dodaje ${statBox(statPercent(percentData))} punktów wymaganych do ulepszenia przedmiotu`;
            },
            "combatHealthReduction": (pointsData: string) => {
                return `Obniża właścicielowi ${statBox(pointsData)} punktów przywracania życia podczas walki`;
            },
            "chanceToBlockPuncture": (percentData: string) => {
                return `${statBox(statPercent(statSigner(statNumber(percentData))))} szans na zablokowanie przebicia`;
            },
            "fasterRevivalRecovery": (percentData: string) => {
                return `Przyśpiesza wracanie do siebie o ${statBox(statPercent(statNumber(percentData)))}`;
            },
            "enemyEvasionReduction": (pointsData: string) => {
                return `Podczas ataku unik przeciwnika jest mniejszy o ${statBox(statNumber(pointsData))}`;
            },
            "destroyArmorReduction": (pointsData: string) => {
                return `Obniżenie niszczenia pancerza o ${statBox(statNumber(pointsData))} punktów`;
            },
            "magicDamageAbsorption": (pointsData: string) => {
                return `Absorbuje do ${statBox(statNumber(pointsData))} obrażeń magicznych`;
            },
            "healChanceAfterFight": ([chanceData, pointsData]: string[]) => {
                return `${statBox(statPercent(statNumber(chanceData)))} szans na wyleczenie ${statBox(statNumber(pointsData))} obrażeń po walce`;
            },
            "magicalResistanceReduction": (percentData: string) => {
                return `Niszczenie odporności magicznych o ${statBox(statPercent(statNumber(percentData)))} podczas ciosu`;
            },
            "heal2TurnsReduction": (pointsData: string) => {
                return `Obniżenie leczenia turowego przeciwnika na 2 tury o ${statBox(statNumber(pointsData))}`;
            },
            "restoreHealthPoints": (pointsData: string) => {
                return `Leczy ${statBox(statNumber(pointsData))} punktów życia`;
            },
            "arrowPhysicalDamage": (damageData: string) => {
                return `Atak fizyczny ${statBox(statPositive(statNumber(damageData)))}`;
            },
            "critPowerReduction": (percentData: string) => {
                return `Obniżenie mocy ciosu krytycznego przeciwnika o ${statBox(statPercent(statNumber(percentData)))}`;
            },
            "enchancementPoints": (percentData: string) => {
                return `Dodaje ${statBox(statPositive(statPercent(statNumber(percentData))))} punktów wymaganych do ulepszenia przedmiotu`;
            },
            "enemyManaReduction": (pointsData: string) => {
                return `Podczas obrony redukuje mane przeciwnika o ${statBox(statNumber(statSigner(pointsData)))} punkty`;
            },
            "physicalAbsorption": (pointsData: string) => {
                return `Absorbuje do ${statBox(statNumber(pointsData))} obrażeń fizycznych`;
            },
            "allBaseAttributes": (pointsData: string) => {
                return `Wszystkie cechy ${statBox(statPositive(statNumber(pointsData)))}`;
            },
            "healthPerStrength": (pointsData: string) => {
                return `${statBox(statPositive(pointsData))} życia za 1 pkt siły`;
            },
            "physicalCritPower": (percentData: string) => {
                return `Moc ciosu krytycznego fizycznego ${statBox(statPercent(statSigner(percentData)))}`;
            },
            "absorptionDestroy": (pointsData: string) => {
                return `Niszczenie ${statBox(statNumber(pointsData))} absorpcji przed atakiem`;
            },
            "maxStackableCount": (maxQuantityCount: string) => {
                return `Maksimum ${statBox(statNumber(maxQuantityCount))} sztuk razem`;
            },
            "upgradedByPercent": (currentLevel: string) => {
                const greekLetters = {
                    "1": "I",
                    "2": "II",
                    "3": "III",
                    "4": "IV",
                    "5": "V",
                    "6": "VI"
                };
                return `stopień ulepszenia ${greekLetters[currentLevel]}`;
            },
            "poisonResistance": (percentData: string) => {
                return `Odporność na truciznę ${statBox(statPositive(statPercent(statNumber(percentData))))}`
            },
            "magicalCritPower": (percentData: string) => {
                return `Moc ciosu krytycznego magicznego ${statBox(statPositive(statPercent(statNumber(percentData))))}`;
            },
            "deepWoundChance": ([woundChance, woundDamage]: string[]) => {
                return `Głęboka rana, ${statBox(statPercent(woundChance))} szans na ${statBox(statPositive(woundDamage))} obrażeń`;
            },
            "chanceToCounter": (percentData: string) => {
                return `${statBox(statPositive(statPercent(statNumber(percentData))))} szans na kontratak po ciosie krytycznym`;
            },
            "lightResistance": (percentData: string) => {
                return `Odporność na błyskawice ${statBox(statPercent(statSigner(percentData)))}`;
            },
            "frostResistance": (percentData: string) => {
                return `Odporność na zimno ${statBox(statPercent(statSigner(percentData)))}`;
            },
            "fireResistance": (percentData: string) => {
                return `Odporność na ogień ${statBox(statPercent(statSigner(percentData)))}`;
            },
            "physicalDamage": (damageData: string) => {
                return `Obrażenia fizyczne ${statBox(statPercent(statSigner(damageData)))}`;
            },
            "shortenRevival": (minData: string) => {
                return `Skraca czas nieprzytomności o ${statBox(statSigner(minData))} min`;
            },
            "criticalChance": (percentData: string) => {
                return `Cios krytyczny ${statBox(statPercent(statSigner(percentData)))}`;
            },
            "defenseDestroy": (pointsData: string) => {
                return `Niszczy ${statBox(statNumber(pointsData))} punktów pancerza podczas ciosu`;
            },
            "armorPuncture": (percentData: string) => {
                return `Przebicie pancerza ${statBox(statSigner(percentData))}`;
            },
            "energyDestroy": (pointsData: string) => {
                return `Podczas obrony niszczy ${statBox(statPositive(statNumber(pointsData)))} energii przeciwnika`;
            },
            "poisonDamage": ([damageData, slowData]: string[]) => {
                return `Obrażenia od trucizny ${statBox(statPositive(statNumber(damageData)))}<br>Spowalnia cel o ${statBox((statNumber(slowData) / 100).toFixed(2))} SA`;
            },
            "loweredLevel": (levelData: string) => {
                return `zmniejszone wymagania `
            },
            "attackSpeed": (pointsData: string) => {
                return `SA ${statBox(statPositive(statNumber(+pointsData / 100).toFixed(2)))}`;
            },
            "bagCapacity": (amountData: string) => {
                return `Mieści ${statBox(amountData)} przedmioty`;
            },
            "evadePoints": (pointsData: string) => {
                return `Unik ${statBox(statPositive(statNumber(pointsData)))}`;
            },
            "lightDamage": (damageData: string) => {
                return `Obrażenia od błyskawic ${statBox(statPositive(statNumber(damageData)))}`;
            },
            "frostDamage": ([slowData, damageData]: string[]) => {
                return `Obrażenia od zimna ${statBox(statPositive(statNumber(damageData)))}<br>Zmniejsza o ${statBox(statNumber(slowData) / 100)} szybkość ataku celu`;
            },
            "fireDamage": (damageData: string) => {
                return `Obrażenia od ognia ${statBox(statPositive(statNumber(damageData)))}`;
            },
            "description": (descriptionData: string) => {
                return descriptionData
                    .replaceAll("\\n", "<br>");
            },
            "legendary": (bonusName: string) => {
                switch(bonusName) {
                    case "angelTouchHealingChance": {
                        return `Dotyk anioła: podczas ataku ${statBox(statPercent(statNumber(7)))} szansy na leczenie ran w ciągu trzech najbliższych tur`;
                    };
                    case "superCriticalHitChance": {
                        return `Cios bardzo krytyczny: ${statBox(statPercent(statNumber(13)))} szansy na podwojenie mocy ciosu krytycznego.`;
                    };
                    case "superHealOnLowHealth": {
                        return `Ostatni ratunek: kiedy po otrzymanym ataku zostanie graczowi mniej niż ${statBox(statPercent(statNumber(18)))} życia, zostaje jednorazowo znacznie uleczony.`;
                    };
                    case "superCriticalReduction": {
                        return `Krytyczna osłona: przyjmowane ciosy krytyczne są o ${statBox(statPercent(statNumber(20)))} słabsze.`;
                    };
                    case "superMagicalReduction": {
                        return `Ochrona żywiołów: ${statBox(statPercent(statNumber(16)))} szans na podniesienie wszystkich odporności do maksimum ${statBox(`(${statPercent(statNumber(90))})`)} przy przyjmowaniu ciosu magicznego.`;
                    };
                    case "superPhysicalReduction": {
                        return `Fizyczna osłona: przyjmowane obrażenia fizyczne zmniejszone o ${statBox(statPercent(statNumber(16)))}.`;
                    };
                    case "glareChanceAfterGetHit": {
                        return `Oślepienie: podczas przyjmowania ataku ${statBox(statPercent(statNumber(9)))} szansy na oślepienie przeciwnika i zablokowanie jego najbliższej akcji.`;
                    };
                    case "curseChanceAfterDoHit": {
                        return `Klątwa: podczas udanego ataku ${statBox(statPercent(statNumber(9)))} szans na aktywację klątwy, która zablokuje najbliższą wykonywaną przez przeciwnika akcję.`;
                    };
                };
            },
            "timelimit": ([resetSeconds, resetTimestamp]: string[]) => {
                const timeSeed = new Date().getTime();
                const timelimitId = `tl-${timeSeed}`;
                const timelimitNodes = [
                    `<span role="renewTime">Można używać co ${statBox(statNumber(resetSeconds))} minut(y/e)</span>`,
                    `<span role="usageStatus">Gotowy do użycia</span>`
                ];
                const getRemainingTime = () => resetTimestamp ? Math.floor(statNumber(resetTimestamp) - new Date().getTime() / 1000) : 0;
                (function countdownLogic(tryCount: number = 0) {
                    const rootElement = document.querySelector(`span[role='${timelimitId}']`);
                    if(!rootElement) {
                        return setTimeout(() => countdownLogic(tryCount + 1), 1000);
                    };
                    if(tryCount >= 30) {
                        return false;
                    };
                    const remainingTime = getRemainingTime();
                    const usageStatus = rootElement.querySelector("[role='usageStatus']");
                    if(remainingTime <= 0) {
                        usageStatus.textContent = "Gotowy do użycia";
                        return true;
                    } else {
                        usageStatus.innerHTML = `Gotowy do użycia za ${statBox(remainingTime)} sekund(y/e)`;
                        return setTimeout(() => countdownLogic(0), 1000);
                    };
                })();

                return `<span role="${timelimitId}">${timelimitNodes.join('<br>')}</span>`;
            },
            "expiresAt": (timestampData: string) => {
                return `Skraca czas nieprzytomności o ${statBox(statSigner(timestampData))} min`;
            },
            "intellect": (pointsData: string) => {
                return `Intelekt ${statBox(statSigner(pointsData))}`;
            },
            "strength": (pointsData: string) => {
                return `Siła ${statBox(statPositive(statNumber(pointsData)))}`;
            },
            "blockade": (pointsData: string) => {
                return `Blok ${statBox(statPositive(statNumber(pointsData)))}`;
            },
            "quantity": (quantityData: string) => {
                return `Ilość: ${statBox(statNumber(quantityData))}`;
            },
            "lootWith": (lootData: string) => {
                const [looterName, __looterSex__, groupInfo, lootTs, npcName] = lootData.split(",");

                const lootDate = new Date(+lootTs * 1000);
                const lootHumanDate = `${lootDate.getDate()}/${lootDate.getMonth() + 1}/${lootDate.getFullYear()}`;
                return `W dniu ${lootHumanDate} został(a) pokonany(a) ${npcName} przez ${looterName} ${statNumber(groupInfo) > 1 ? "wraz z drużyną" : ""}`;
            },
            "fullHeal": (remainingData: string) => {
                return `Pełne leczenie, pozostało ${statBox(statNumber(remainingData))} punktów uleczania.`;
            },
            "agility": (pointsData: string) => {
                return `Zręczność ${statBox(statSigner(pointsData))}`;
            },
            "defense": (pointsData: string) => {
                return `Pancerz: ${statBox(statNumber(pointsData))}`;
            },
            "health": (pointsData: string) => {
                return `Życie ${statBox(statPositive(statNumber(pointsData)))}`;
            },
            "energy": (pointsData: string) => {
                return `Energia ${statBox(statPositive(statNumber(pointsData)))}`;
            },
            "mana": (pointsData: string) => {
                return `Mana ${statBox(statPositive(statNumber(pointsData)))}`;
            },
        },
        "tags": {
            "isBindPermamentlyAfterBuy": () => {
                return "Wiąże na stałe po kupieniu";
            },
            "isPermamentlyBounded": () => {
                return "Związany z właścicielem na stałe";
            },
            "isBindsAfterEquip": () => {
                return "Wiąże po założeniu";
            },
            "isBoundToOwner": () => {
                return "Zwiążany z włascicielem";
            },
            "isRecovered": () => {
                return "Przedmiot odzyskany, obniżona wartość";
            },
        },
    },
    "rarity": {
        "legendary": "legendarny",
        "artefact": "artefakt",
        "heroic": "heroiczny",
        "unique": "unikatowy",
        "common": "pospolity"
    },
};

type itemSchema = {
    inner: {
        attributes: Partial<{
            requirements: Partial<{
                [k in keyof typeof translations.attributes.requirements]: boolean | object | string | number
}> & Partial<{
    needProfessions: Array<keyof typeof translations.professions>
}>,
    bonuses: Partial<{
    [k in keyof typeof translations.attributes.bonuses]: boolean | object | string | number
}>,
tags: Partial<{
    [k in keyof typeof translations.attributes.tags]: boolean | object | string | number
}>,
}>,
category: keyof typeof translations.categories,
    currency: "draconite" | "honor" | "gold",
    rarity: keyof typeof translations.rarity,
    price: number,
    src: string,
    name: string,
    id: number
},
hero: {
    profession: keyof typeof translations.professions,
        level: number,
},
};

const itemProperties = defineProps<{
    fromString: string,
    config: {
        legendaryBonusThick: boolean,
        separateDescription: boolean,
        colorizeAttributes: boolean,
        previewRightCorner: boolean,
        previewUnderName: boolean,
        previewShow: boolean
    }
}>();

const itemInstance = { "value": JSON.parse(itemProperties.fromString) as itemSchema };
watch(itemProperties, function() {
    itemInstance.value = JSON.parse(itemProperties.fromString);
    console.log('itemInstance', itemInstance.value)
});
</script>

<template>
    {{itemInstance.value.inner.attributes}}
    <slot v-if="itemInstance.value">
        <div class="rockTip">
            <div class="header">
                <div class="name">
                    <span>{{ itemInstance.value.inner.name }}</span>
                </div>
                <div class="rarity" :type="itemInstance.value.inner.rarity">
                    <span>* </span>
                    <span class="inner">
                        {{  `${translations.rarity[itemInstance.value.inner.rarity]}` }}
                    </span>
                    <span> *</span>
                </div>
                <slot v-if="itemProperties.config.previewShow">
                    <div class="centrialize">
                        <div class="preview" :type="itemInstance.value.inner.rarity">
                            <img :src="`https://s3.letscode.it/virtigia-assets/img/${itemInstance.value.inner.src}`" />
                        </div>
                    </div>
                </slot>
            </div>
            <div class="struct">
                <span>{{ `Typ: ${translations.categories[itemInstance.value.inner.category]}` }}</span>
                <div class="bonuses">
                    <slot v-for="currentStat of Object.keys(itemInstance.value.inner.attributes.bonuses)">
                        <slot v-if="currentStat === 'description' && itemProperties.config.separateDescription">
                            <div class="separator" />
                        </slot>
                        <div class="attribute" :stat="currentStat">
                            <span v-html="translations.attributes.bonuses[currentStat].apply(null, [itemInstance.value.inner.attributes.bonuses[currentStat]])" />
                        </div>
                        <slot v-if="currentStat === 'description' && itemProperties.config.separateDescription">
                            <div class="separator" />
                        </slot>
                    </slot>
                </div>
                <div class="tags">
                    <slot v-if="itemInstance.value.inner.attributes.tags">
                        <slot v-for="currentStat of Object.values(itemInstance.value.inner.attributes.tags)">
                            <div class="attribute" :stat="currentStat">
<!--                                {{translations.attributes.tags[currentStat]}}-->
                                <span v-html="translations.attributes.tags[currentStat].apply(null, [itemInstance.value.inner.attributes.tags[currentStat]])" />
                            </div>
                        </slot>
                    </slot>
                </div>
                <div class="requirements">
                    <slot v-for="currentStat of Object.keys(itemInstance.value.inner.attributes.requirements)">
                        <div class="attribute" category="requirements" :stat="currentStat" :fulfilling="(() => {
                            switch(currentStat) {
                                case 'needProfessions': {
                                    return itemInstance.value.inner.attributes.requirements.needProfessions.includes(itemInstance.value.hero.profession);
                                };
                                case 'needLevel': {
                                    return itemInstance.value.hero.level >= statNumber(itemInstance.value.inner.attributes.requirements.needLevel);
                                };
                                default: {
                                    return false;
                                }
                            };
                        })()">
                            <span v-html="translations.attributes.requirements[currentStat].apply(null, [itemInstance.value.inner.attributes.requirements[currentStat]])" />
                        </div>
                    </slot>
                </div>
            </div>
            <div class="footer">
                <span>{{ `Wartość: ${parsePrice(itemInstance.value.inner.price)}` }}</span>
                <div :role="`${itemInstance.value.inner.currency}-currency`" />
            </div>
        </div>
    </slot>
</template>

<style>
:root {
    --rockTip-font: Verdana, Arial, sans-serif;

    --rockTip-draconiteCurrencyIcon: url(data:image/jpeg;base64,iVBORw0KGgoAAAANSUhEUgAAABYAAAAdCAYAAACuc5z4AAABhGlDQ1BJQ0MgcHJvZmlsZQAAKJF9kT1Iw1AUhU9TtVIqDnYQ6ZChOtlFRRxrFYpQIdQKrTqYvPQPmhiSFBdHwbXg4M9i1cHFWVcHV0EQ/AFxF5wUXaTE+5JCixgvPN7Hefcc3rsPEJo1plk9SUDTbTObTon5wooYekUAYfQhBlFmljErSRn41tc9dVPdJXiWf9+fNaAWLQYEROIkM0ybeJ14etM2OO8TR1lFVonPicdNuiDxI9cVj984l10WeGbUzGXniKPEYrmLlS5mFVMjniKOq5pO+ULeY5XzFmetVmfte/IXRor68hLXacWQxgIWIUGEgjqqqMFGgnadFAtZOk/5+Edcv0QuhVxVMHLMYwMaZNcP/ge/Z2uVJie8pEgK6H1xnI9RILQLtBqO833sOK0TIPgMXOkd/0YTmPkkvdHR4kfA4DZwcd3RlD3gcgcYfjJkU3alIC2hVALez+ibCsDQLRBe9ebWPsfpA5CjWWVugINDYKxM2Ws+7+7vntu/Pe35/QBgX3KfMQzJDAAAAAZiS0dEAP8A/wD/oL2nkwAAAAlwSFlzAAAuIwAALiMBeKU/dgAAAAd0SU1FB+kBCgYdDF0VdbsAAAAZdEVYdENvbW1lbnQAQ3JlYXRlZCB3aXRoIEdJTVBXgQ4XAAADAElEQVRIx7WWX0hTURzHP3OVgwsxMeVSIhvowwWjWzQaSBCNIiLBF8FRiKSgoIERgSTBQIq9tQcDgxl7CYO9TBYR/aOXYGDIIGGSQkNMbog4ImFD4vYg53R3d6cz6sLhcM85v8/5/n7nz+/Af/pcf2ln7seoqxFiWmBmMqywU5okpLntk9SuLBlWzJ3SpJkMK3KC3JTfDGlua9vBwQIiagEMaW67JzXFWA5OhhWms0XZMax76Jnd3nftXFXcp6OzmdHHqxI2nS3yLvcLgKHBAS50XaJNPSGNTp4OAOA54qkO3ilNckWPMKx76OhsRhv9KmEAa6sbPHodocHnA0BVvRhGgcSNGIFz5wFch5z8SPVHpco25SKzc5dYW93gTnxEwrSgjmEUCOpeMtlCbdutZ3abt0ovg9FntJ3SuRMfIfElgRbUAQjq3jKoqnr3BZtDgwPMzj0vAzb4fNL43tUAeWO3FlDDKLCVz7NifHMEmyKOTgoBYte6eb++TOxaN08XliV0r5NXBhVxtLp780w7sU8pLh5vZ+xFCp+62y88uX05wof0m0rFVqgYrKq70KDu5enCMmNnd5X6VCrC0NLaxJP4jDwbdULt4uLnMqXCyKq4N55whJaMonMojvlVplMx2SiMrIofvpyX/1YowHhftCwMAIcAvEdV6lWPbBQLYoVb23OZrBxbMoq0tDZx99ZM2RVRsY+FCusk1n26lc/LkFVTK8GFH4ajkd0D0S9qoXblY8J+d/9RLBZAKLYrt0O38nnG+6LEx68zrHvITfnJTflJhhUA0yV2BUD6e0qC7JAGnw8jsyTXomQU2ZzfJhlW5DUa0ty8ykY4XH+fOsD1JD7DMb8qVZeMotxGQrmRWUL/WWQ9vcl6epPN+W1CmpuOzmYpZmqklVR/tPK6nHgwYTYGFPN4V6PZGFDKisgcIk2FNHdFytoro0i4vTilqb1AjhlZGDsATJFgD5pIpYsicVrh4l+oP4hi7Iqs6p2eAJYJan7pVCvV3hv/5Im17zPrN6ZrCsZqyntqAAAAAElFTkSuQmCC);
    --rockTip-honorCurrencyIcon: url(data:image/jpeg;base64,R0lGODlhIAAgAOezAAAAAAUGBAoCAgkJBwgICHNMABIMAfmkACEZBRYPA4tcATIkCBURCYRoSoiJnVg/AllccdGZB049IjkoBqpzBQYGBl1hd8DBwtjX1re3uLi4u8B8AEowAWdEAdDQ0JNgAGRKFEk5DhAOCVBDK8CNJ2dXOYxsLBUWGSskFqBTAFBAHu6cANuPAEYwBVI3AqFqAS0iAkIsAs6WB6JyAJplALt7AJ1yBdOKAMWAAHV0efzPd2lzjc2xeVZZcIRpT92qSE1RaUVIYG54jbCwsqKmstjZ2a+yva6usP/dlnN8j4eIm4aFmKuJQ3ZmRT0vAuapL2hWMf/ZimZzh1dKL5ZqAaeAMtKdMy4eAn19kMCFMUZBLINsPpltLtW1c21tdW9wd2VpgJFZA35WAyQbBWJmfiEXBVE2Ac3O0M/P1dbW2cbGyMrLzY1jADklAB0TAJh6PW5IAGlEAF09ALB1AMmbPeqaAOCSAJ+jr29XN2FXOdGYObq6vsjGwqeqtquvusqEAGZQB+S1Vs+bFENIXr+IGIRLGDouFpJoE4hoE3ZVE0Y1FNSUE7Z/E9WqVYpoIXJXIJ1eH2BMJtOiJd6fJK18HBoaHHhdGsaRKNGfHN6dHKKMYJNlCIFeCGtKCTUmCUtBKraebMeRB9SQB7GXYNGZKuGZB6l8CZiBEE86D/bEXl9KE82QEqp2Ec6ODKOAC6lhC+CaDcGXDXlbDf///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////yH5BAEKAP8ALAAAAAAgACAAAAj+AP8JHEhQIICDCBEWXMhw4AAACcx0UEDxQYyHABpqFDHggY0IMmSE+hMhQilRHxYACKCRYIAALUzFcgUIxgABAAQICMGpFaxNCFi2DDCgk6BTTjAmPCgggapFqyYIZfjyECZEBpZqPYiAUCYXUwsGSCQJRICtaBNQmuQprEFUTyzdRLtVAINLpBKEJWql0NmDGS6gvZBhKSRHGQcCeJQFJ0IHSTBoxSDEgVY9YxKv/MElgOODR3rsSajBwhGtAX6Y0GwolYqtSix4OHjGwhK0TAId/Adgiw4GaIlELiLkDl0oOlBkBDCqy8qEFRAaQQOmz0ECSv+iiDJlOQ9QS8/+zgaQhsySSgf5bGWApIl3TUsrZCgC4MIOIidODAHgYQgBre0tx8QbSgHwXw9r7GAZAFhIoQYEzyXEQCORLGdCFVv5AIERCfkBgQ8iaIUAHYosBwIJWSFEQAMlfALEF4MA4AUQWuTRQIEAhEACAsstwEgMTK2IhwQHBVHkQRKU0MB/CMnCym4HUSDGYyNI8JlWKoygBJMA2NBBYry5MIdKAGiQw19oCZCDBkwGMIMbYBL1Qhg4DcAlWgQQ8JcArxQApkET1AAHXXQZQAUFCfxpUAEbyHFlQv9Fh5ABbGxwhaIGAfDBDQpg9NCdCSEwww0cYKoYAAXYsUGpj05aAAtDf7RhKkEHTYDDCjgUQCZCLSjAwgovlDHrQgd1UMMByOLwxw3I1kEBDLu1RCwABjxQAA00fBBHqdFK2xBd3oYrLkEBAQA7);
    --rockTip-goldCurrencyIcon: url(data:image/jpeg;base64,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);
    --rockTip-baseIcon: url(data:image/jpeg;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAMAAAC6V+0/AAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAABRUExURUIdFlslHGQoHmIoHjoaFGAmHWgpH20qID4bFWMoHjEWEkceF0keF10lHEMdFlwlHFkkG1gkG1MjGk4hGVckG1IjGkofGE8hGUsgGFAiGVUkGz3o9X0AAAD3SURBVHjajI/HbQVBDEO18dveCRplqf9Cve7A70KAIAkQqlUTd/bHcVXGhhwQLxze4qc2mZeyF0G9mLE394s8rdUUOPVUaZJjfGUNcKongIQkhfPdLG8w+hYB+RLtaSg3MascZACoqMtimXdqYzIXM6Cjl+VaUqvAn6/12aFz58dJZIll84yVDlttFRmcamHtHOnYQVycqS6hUjGz2PcFwMBG8baGyZ88lmDTpg7L+65A1O8dc0DHjqTvP86g3ObU1zzGMbT7FqOv3m9RjA2e8zl1zWKZR6/S1H6ADRvXQgXO+RGpqK4wX1CTlPYqXMS26P/JXwEGAFT0G97fPlreAAAAAElFTkSuQmCC);

    --rockTip-descriptionColor: hsl(60deg, 100%, 90%);
    --rockTip-lootlogColor: hsl(120deg, 100%, 50%);
    --rockTip-borderColor: hsl(50deg, 35%, 50%);
    --rockTip-baseColor: hsl(10deg, 50%, 20%);
    --rockTip-nameColor: hsl(60deg, 45%, 65%);

    --rockTip-legendaryRarity: hsl(35deg, 95%, 60%);
    --rockTip-artefactRarity: hsl(50deg, 100%, 50%);
    --rockTip-upgradedRarity: hsl(350deg, 100%, 50%);
    --rockTip-heroicRarity: hsl(200deg, 100%, 65%);
    --rockTip-uniqueRarity: hsl(60deg, 100%, 45%);
    --rockTip-commonRarity: hsl(200deg, 5%, 70%);

    --rockTip-fulfillErr: hsl(350deg, 70%, 55%);
    --rockTip-fulfillOk: hsl(50deg, 100%, 45%);
}

#app {
    display: flex;
    gap: 0.5rem;
}

.rockTip {
    background-image: var(--rockTip-baseIcon);
    background-color: var(--rockTip-baseColor);
    border-radius: 0.5rem;
    font-family: var(--rockTip-font);
    font-size: 0.75rem;
    position: relative;
    display: block;
    padding: 0.30rem;
    border: 0.20rem double var(--rockTip-borderColor);
    color: white;

    max-width: 300px;
    height: fit-content;
    width: fit-content;
}

.rockTip > .item {
    padding-right: 1rem;
    padding-left: 1rem;
}
.rockTip > .item {
    flex-direction: column;
    display: flex;
}

.rockTip > .header > .name,
.rockTip > .header > .rarity {
    font-weight: bold;
    text-align: center;
    word-break: break-word;
    color: var(--rockTip-nameColor);
}
.rockTip > .header > .rarity[type='legendary'] > .inner {
    background-image: url(https://i.imgur.com/u3QyGwC.gif);
}
.rockTip > .header > .rarity[type='legendary'] {
    color: var(--rockTip-legendaryRarity);
}
.rockTip > .header > .rarity[type='artefact'] {
    color: var(--rockTip-artefactRarity);
}
.rockTip > .header > .rarity[type='upgraded'] {
    color: var(--rockTip-upgradedRarity);
}
.rockTip > .header > .rarity[type='heroic'] {
    color: var(--rockTip-heroicRarity);
}
.rockTip > .header > .rarity[type='unique'] {
    color: var(--rockTip-uniqueRarity);
}
.rockTip > .header > .rarity[type='common'] {
    color: var(--rockTip-commonRarity);
}

.rockTip > .header > * > .preview {
    image-rendering: pixelated;
    justify-content: center;
    background-size: cover;
    backdrop-filter: blur(5px);
    margin-bottom: 0.25rem;
    border-radius: 0.5rem;
    align-items: center;
    margin-top: 0.25rem;
    display: flex;
    height: 2.5rem;
    width: 2.5rem;
}
.rockTip > .header > * > .centrialize {
    justify-content: center;
    align-items: center;
    display: flex;
}
.rockTip > .header > * > .preview[type='legendary'] {
    box-shadow: inset 0 0 11px 0px var(--rockTip-legendaryRarity);
}
.rockTip > .header > * > .preview[type='artefact'] {
    box-shadow: inset 0 0 11px 0px var(--rockTip-artefactRarity);
}
.rockTip > .header > * > .preview[type='upgraded'] {
    box-shadow: inset 0 0 11px 0px var(--rockTip-upgradedRarity);
}
.rockTip > .header > * > .preview[type='heroic'] {
    box-shadow: inset 0 0 11px 0px var(--rockTip-heroicRarity);
}
.rockTip > .header > * > .preview[type='unique'] {
    box-shadow: inset 0 0 11px 0px var(--rockTip-uniqueRarity);
}
.rockTip > .header > * > .preview[type='common'] {
    box-shadow: inset 0 0 11px 0px var(--rockTip-commonRarity);
}

.rockTip > .struct > * > .separator {
    margin-bottom: 0.5rem;
    margin-top: 0.5rem;
    background: var(--rockTip-borderColor);
    height: 1px;
}
.rockTip > .struct > * > .attribute > * > [role='value-colorized'] {
    color: gold;
}
.rockTip > .struct > * > .attribute[category='requirements'] {
    font-weight: bold;
}
.rockTip > .struct > * > .attribute[category='requirements'][fulfilling='true'],
.rockTip > .struct > * > .attribute[category='requirements'][fulfilling='true'] {
    color: var(--rockTip-fulfillOk);
}
.rockTip > .struct > * > .attribute[category='requirements'][fulfilling='false'],
.rockTip > .struct > * > .attribute[category='requirements'][fulfilling='false'] {
    color: var(--rockTip-fulfillErr)
}


.rockTip > .struct > * > .attribute[stat='description'] {
    text-align: center;
    color: var(--rockTip-descriptionColor);
}
.rockTip > .struct > * > .attribute[stat='legendary'] {
    color: var(--rockTip-legendaryRarity);
}
.rockTip > .struct > * > .attribute[stat='lootWith'] {
    color: var(--rockTip-lootlogColor);
}

.rockTip > .footer {
    display: flex;
    gap: 0.25rem;
}
.rockTip > .footer > [role='draconite-currency'] {
    background-image: var(--rockTip-draconiteCurrencyIcon);
    height: 1.875rem;
    width: 1.25rem;
    zoom: 0.65;
}
.rockTip > .footer > [role='honor-currency'] {
    background-image: var(--rockTip-honorCurrencyIcon);
    margin: -0.25rem 0rem 0rem 0rem;
    height: 2.063rem;
    width: 2.063rem;
    zoom: 0.75;
}
.rockTip > .footer > [role='gold-currency'] {
    background-image: var(--rockTip-goldCurrencyIcon);
    height: 1.3rem;
    width: 1.3rem;
    zoom: 0.85;
}
</style>
