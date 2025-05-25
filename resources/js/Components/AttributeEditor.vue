<template>
  <div class="attribute-editor">
    <div class="card mb-4">
      <h3 class="text-xl font-bold mb-2">Edytor atrybutów</h3>
      <div class="flex flex-wrap gap-2 mb-4">
        <div v-for="(value, key) in attributes" :key="key" class="attribute-tag p-2 bg-blue-100 rounded flex items-center">
          <span>{{ getAttributeLabel(key, value) }}</span>
          <button @click="removeAttribute(key)" class="ml-2 text-red-500 hover:text-red-700">
            <i class="pi pi-times"></i>
          </button>
        </div>
      </div>

      <div class="mb-4">
        <AutoComplete
          v-model="selectedAttribute"
          :suggestions="filteredAttributes"
          @complete="searchAttributes"
          placeholder="Wyszukaj atrybut..."
          class="w-full"
          dropdown
          forceSelection
          optionLabel="label"
        >
          <template #item="slotProps">
            <div>{{ slotProps.item.label }}</div>
          </template>
        </AutoComplete>
      </div>

      <div v-if="selectedAttribute && selectedAttribute.key" class="mb-4">
        <div class="attribute-input">
          <!-- Special handling for InputNumber with prefix/suffix -->
          <div v-if="getInputComponent(selectedAttribute.key) === 'InputNumber' && (getAttributePrefix(selectedAttribute.key) || getAttributeSuffix(selectedAttribute.key))" class="p-inputgroup">
            <span v-if="getAttributePrefix(selectedAttribute.key)" class="p-inputgroup-addon">{{ getAttributePrefix(selectedAttribute.key) }}</span>
            <InputNumber v-model="attributeValue" class="w-full" :placeholder="'Wartość dla ' + selectedAttribute.label" />
            <span v-if="getAttributeSuffix(selectedAttribute.key)" class="p-inputgroup-addon">{{ getAttributeSuffix(selectedAttribute.key) }}</span>
          </div>
          <!-- Default handling for other components -->
          <component
            v-else
            :is="getInputComponent(selectedAttribute.key)"
            v-model="attributeValue"
            v-bind="getComponentProps(selectedAttribute)"
            :options="getAttributeOptions(selectedAttribute.key)"
            class="w-full"
            :placeholder="'Wartość dla ' + selectedAttribute.label"
            :optionLabel="getInputComponent(selectedAttribute.key) === 'Dropdown' || getInputComponent(selectedAttribute.key) === 'MultiSelect' ? 'label' : undefined"
            :optionValue="getInputComponent(selectedAttribute.key) === 'Dropdown' || getInputComponent(selectedAttribute.key) === 'MultiSelect' ? 'value' : undefined"
          />
        </div>
        <div v-if="isArrayAttribute(selectedAttribute.key)" class="mt-2">
          <Button @click="addArrayValue" label="Dodaj wartość" class="p-button-sm" />
          <div v-if="arrayValues.length > 0" class="mt-2">
            <div v-for="(val, index) in arrayValues" :key="index" class="flex items-center mb-1">
              <InputNumber v-model="arrayValues[index]" class="w-full" />
              <Button @click="removeArrayValue(index)" icon="pi pi-times" class="p-button-danger p-button-sm ml-2" />
            </div>
          </div>
        </div>
        <Button @click="addAttribute" label="Dodaj atrybut" class="mt-2" />
      </div>
    </div>

    <div class="card">
      <h3 class="text-xl font-bold mb-2">Podgląd JSON</h3>
      <pre class="bg-gray-100 p-4 rounded overflow-auto max-h-96">{{ JSON.stringify(attributes, null, 2) }}</pre>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import AutoComplete from 'primevue/autocomplete';

const attributes = defineModel<Record<string, any>>('attributes');


const selectedAttribute = ref<any>(null);
const attributeValue = ref<any>(null);
const arrayValues = ref<any[]>([]);
const filteredAttributes = ref<any[]>([]);

// List of available attributes with their metadata
const availableAttributes = [
  // Limits
  { key: 'needProfessions', label: 'Wymagana profesja', type: 'multiselect', options: [
    { value: 'b', label: 'Tancerz ostrzy' },
    { value: 't', label: 'Tropiciel' },
    { value: 'w', label: 'Wojownik' },
    { value: 'p', label: 'Paladyn' },
    { value: 'h', label: 'Łowca' },
    { value: 'm', label: 'Mag' }
  ]},
  { key: 'needIntellect', label: 'Wymagany intelekt', type: 'number' },
  { key: 'needStrength', label: 'Wymagana siła', type: 'number' },
  { key: 'needAgility', label: 'Wymagana zręczność', type: 'number' },
  { key: 'needLevel', label: 'Wymagany poziom', type: 'number' },

  // Bonuses
  { key: 'criticalReductionDuringDefending', label: 'Redukcja krytyka podczas obrony', type: 'number', suffix: '%' },
  { key: 'manaEnergyDestroyReduction', label: 'Obniżanie niszczenia many i energii', type: 'number' },
  { key: 'magicalResistanceReduction', label: 'Niszczenie odporności magicznych', type: 'number', suffix: '%' },
  { key: 'enemyAttackSpeedReduction', label: 'Obniżenie szybkości ataku przeciwnika', type: 'number' },
  { key: 'healthRestorationPercent', label: 'Odnowienie punktów życia', type: 'number', suffix: '%' },
  { key: 'combatHealthRestoration', label: 'Przywrócenie życia podczas walki', type: 'number' },
  { key: 'addEnchancementPoints', label: 'Dodanie punktów ulepszenia', type: 'number', suffix: '%' },
  { key: 'combatHealthReduction', label: 'Obniżenie przywracania życia podczas walki', type: 'number' },
  { key: 'chanceToBlockPuncture', label: 'Szansa na zablokowanie przebicia', type: 'number', suffix: '%' },
  { key: 'fasterRevivalRecovery', label: 'Przyśpieszenie wracania do siebie', type: 'number', suffix: '%' },
  { key: 'enemyEvasionReduction', label: 'Obniżenie szansy na unik przeciwnika', type: 'number' },
  { key: 'destroyArmorReduction', label: 'Obniżenie niszczenia pancerza', type: 'number' },
  { key: 'magicDamageAbsorption', label: 'Absorpcja obrażeń magicznych', type: 'number' },
  { key: 'healChanceAfterFight', label: 'Szansa na wyleczenie po walce', type: 'array', arraySize: 2 },
  { key: 'heal2TurnsReduction', label: 'Obniżenie leczenia turowego przeciwnika', type: 'number' },
  { key: 'restoreHealthPoints', label: 'Leczenie punktów życia', type: 'number' },
  { key: 'arrowPhysicalDamage', label: 'Atak fizyczny strzały', type: 'number' },
  { key: 'critPowerReduction', label: 'Obniżenie mocy ciosu krytycznego', type: 'number', suffix: '%' },
  { key: 'enchancementPoints', label: 'Punkty ulepszenia', type: 'number', suffix: '%' },
  { key: 'enemyManaReduction', label: 'Redukcja many przeciwnika', type: 'number' },
  { key: 'physicalAbsorption', label: 'Absorpcja obrażeń fizycznych', type: 'number' },
  { key: 'allBaseAttributes', label: 'Wszystkie cechy', type: 'number' },
  { key: 'healthPerStrength', label: 'Życie za punkt siły', type: 'number' },
  { key: 'physicalCritPower', label: 'Moc ciosu krytycznego fizycznego', type: 'number', suffix: '%' },
  { key: 'absorptionDestroy', label: 'Niszczenie absorpcji', type: 'number' },
  { key: 'maxQuantity', label: 'Maksymalna ilość', type: 'number' },
  { key: 'upgradedByPercent', label: 'Stopień ulepszenia', type: 'dropdown', options: [
    { value: '1', label: 'I' },
    { value: '2', label: 'II' },
    { value: '3', label: 'III' },
    { value: '4', label: 'IV' },
    { value: '5', label: 'V' },
    { value: '6', label: 'VI' }
  ]},
  { key: 'poisonResistance', label: 'Odporność na truciznę', type: 'number', suffix: '%' },
  { key: 'magicalCritPower', label: 'Moc ciosu krytycznego magicznego', type: 'number', suffix: '%' },
  { key: 'deepWoundChance', label: 'Szansa na głęboką ranę', type: 'array', arraySize: 2 },
  { key: 'chanceToCounter', label: 'Szansa na kontratak', type: 'number', suffix: '%' },
  { key: 'lightResistance', label: 'Odporność na błyskawice', type: 'number', suffix: '%' },
  { key: 'frostResistance', label: 'Odporność na zimno', type: 'number', suffix: '%' },
  { key: 'fireResistance', label: 'Odporność na ogień', type: 'number', suffix: '%' },
  { key: 'physicalDamage', label: 'Obrażenia fizyczne', type: 'array', arraySize: 2 },
  { key: 'shortenRevival', label: 'Skrócenie czasu nieprzytomności', type: 'number' },
  { key: 'criticalChance', label: 'Cios krytyczny', type: 'number', suffix: '%' },
  { key: 'defenseDestroy', label: 'Niszczenie pancerza', type: 'number' },
  { key: 'armorPuncture', label: 'Przebicie pancerza', type: 'number', suffix: '%' },
  { key: 'keyDescription', label: 'Opis klucza', type: 'text' },
  { key: 'energyDestroy', label: 'Niszczenie energii przeciwnika', type: 'number' },
  { key: 'healRemaining', label: 'Pozostałe punkty uleczania', type: 'number' },
  { key: 'poisonDamage', label: 'Obrażenia od trucizny', type: 'array', arraySize: 2 },
  { key: 'attackSpeed', label: 'Szybkość ataku', type: 'number', suffix: '%' },
  { key: 'bagCapacity', label: 'Pojemność torby', type: 'number' },
  { key: 'description', label: 'Opis', type: 'text' },
  { key: 'evadePoints', label: 'Punkty uniku', type: 'number' },
  { key: 'lightDamage', label: 'Obrażenia od błyskawic', type: 'number' },
  { key: 'frostDamage', label: 'Obrażenia od zimna', type: 'array', arraySize: 2 },
  { key: 'fireDamage', label: 'Obrażenia od ognia', type: 'number' },
  { key: 'legendaryBon', label: 'Bonus legendarny', type: 'array', arraySize: 2 },
  { key: 'teleportTo', label: 'Teleport do', type: 'array', arraySize: 4 },
  { key: 'cooldownTime', label: 'Czas odnowienia', type: 'array', arraySize: 2 },
  { key: 'expiresAt', label: 'Wygasa po', type: 'number' },
  { key: 'intellect', label: 'Intelekt', type: 'number' },
  { key: 'strength', label: 'Siła', type: 'number' },
  { key: 'quantity', label: 'Ilość', type: 'number' },
  { key: 'agility', label: 'Zręczność', type: 'number' },
  { key: 'defense', label: 'Pancerz', type: 'number' },
  { key: 'health', label: 'Życie', type: 'number' },
  { key: 'energy', label: 'Energia', type: 'number' },
  { key: 'blockPoints', label: 'Punkty bloku', type: 'number' },
  { key: 'mana', label: 'Mana', type: 'number' },
  { key: 'physicalDamageAbsorption', label: 'Absorpcja obrażeń fizycznych', type: 'number' },
  { key: 'magicalDamageAbsorption', label: 'Absorpcja obrażeń magicznych', type: 'number' },
  { key: 'incrementGold', label: 'Zwiększenie złota', type: 'number' },

  // Tags
  { key: 'isNonStoreableInClanDeposit', label: 'Nie można przechowywać w depozycie klanowym', type: 'boolean' },
  { key: 'isBindPermamentlyAfterBuy', label: 'Wiąże na stałe po kupieniu', type: 'boolean' },
  { key: 'isNonStoreableInDeposit', label: 'Nie można przechowywać w depozycie', type: 'boolean' },
  { key: 'isPermamentlyBounded', label: 'Związany z właścicielem na stałe', type: 'boolean' },
  { key: 'isBindsAfterEquip', label: 'Wiąże po założeniu', type: 'boolean' },
  { key: 'isNotAuctionable', label: 'Nie można wystawiać na aukcję', type: 'boolean' },
  { key: 'isBoundToOwner', label: 'Związany z właścicielem', type: 'boolean' },
  { key: 'isRecovered', label: 'Przedmiot odzyskany', type: 'boolean' },
  { key: 'isUnidentified', label: 'Przedmiot niezidentyfikowany', type: 'boolean' },
  { key: 'useOutfit', label: 'Zmiana wyglądu postaci', type: 'object' }
];

// Search attributes based on user input
const searchAttributes = (event: any) => {
  const query = event.query.toLowerCase();
  filteredAttributes.value = availableAttributes.filter(attr =>
    attr.label.toLowerCase().includes(query) || attr.key.toLowerCase().includes(query)
  );
};

// Watch for changes in selectedAttribute to update attributeValue and set prefix/suffix
watch(selectedAttribute, (newValue) => {
  if (newValue) {
    attributeValue.value = null;
    arrayValues.value = [];
  }
});

// Get the appropriate input component based on attribute type
const getInputComponent = (key: string) => {
  const attr = availableAttributes.find(a => a.key === key);
  if (!attr) return 'InputText';

  switch (attr.type) {
    case 'number':
      return 'InputNumber';
    case 'text':
      return 'InputText';
    case 'boolean':
      return 'Checkbox';
    case 'dropdown':
      return 'Dropdown';
    case 'multiselect':
      return 'MultiSelect';
    case 'array':
      return 'InputNumber'; // For the first value in array
    default:
      return 'InputText';
  }
};

// Check if attribute is an array type
const isArrayAttribute = (key: string) => {
  const attr = availableAttributes.find(a => a.key === key);
  return attr && attr.type === 'array';
};

// Get attribute options for dropdown/multiselect
const getAttributeOptions = (key: string) => {
  const attr = availableAttributes.find(a => a.key === key);
  return attr && attr.options ? attr.options : [];
};

// Get attribute prefix
const getAttributePrefix = (key: string) => {
  const attr = availableAttributes.find(a => a.key === key);
  return attr?.prefix || '';
};

// Get attribute suffix
const getAttributeSuffix = (key: string) => {
  const attr = availableAttributes.find(a => a.key === key);
  return attr?.suffix || '';
};

// Get component-specific props
const getComponentProps = (attribute: any) => {
  const componentType = getInputComponent(attribute.key);
  const props: Record<string, any> = {};

  // Handle different component types
  if (componentType === 'InputNumber') {
    // For InputNumber, we don't pass prefix/suffix directly
    // Instead, we use a custom wrapper with p-inputgroup
  } else {
    // For other components that support prefix/suffix
    const prefix = getAttributePrefix(attribute.key);
    const suffix = getAttributeSuffix(attribute.key);

    if (prefix) props.prefix = prefix;
    if (suffix) props.suffix = suffix;
  }

  return props;
};

// Add attribute to the list
const addAttribute = () => {
  if (!selectedAttribute.value || !selectedAttribute.value.key) return;

  const key = selectedAttribute.value.key;
  const attr = availableAttributes.find(a => a.key === key);

  if (!attr) return;

  if (attr.type === 'boolean') {
    attributes.value[key] = attributeValue.value || true;
  } else if (attr.type === 'array' && arrayValues.value.length > 0) {
    attributes.value[key] = [...arrayValues.value];
  } else {
    attributes.value[key] = attributeValue.value;
  }

  // Reset form
  selectedAttribute.value = null;
  attributeValue.value = null;
  arrayValues.value = [];
};

// Remove attribute from the list
const removeAttribute = (key: string) => {
  const newAttributes = {...attributes.value};
  delete newAttributes[key];
  attributes.value = newAttributes;
};

// Add a value to the array for array-type attributes
const addArrayValue = () => {
  if (attributeValue.value !== null && attributeValue.value !== undefined) {
    arrayValues.value.push(attributeValue.value);
    attributeValue.value = null;
  }
};

// Remove a value from the array
const removeArrayValue = (index: number) => {
  arrayValues.value.splice(index, 1);
};

// Get a human-readable label for the attribute
const getAttributeLabel = (key: string, value: any) => {
  const attr = availableAttributes.find(a => a.key === key);
  if (!attr) return key;

  if (attr.type === 'boolean') {
    return attr.label;
  } else if (attr.type === 'array') {
    if (Array.isArray(value)) {
      return `${attr.label}: [${value.join(', ')}]`;
    } else {
      return `${attr.label}: [${value}]`;
    }
  } else if (attr.type === 'multiselect') {
    const options = attr.options || [];
    const labels = Array.isArray(value)
      ? value.map(v => {
          const option = options.find(o => o.value === v);
          return option ? option.label : v;
        }).join(', ')
      : value;
    return `${attr.label}: ${labels}`;
  } else {
    return `${attr.label}: ${value}${attr.suffix || ''}`;
  }
};
</script>

<style scoped>
.attribute-editor {
  margin-bottom: 2rem;
}

.attribute-tag {
  transition: all 0.2s;
}

.attribute-tag:hover {
  background-color: #e0e7ff;
}
</style>
