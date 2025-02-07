import { defineStore } from "pinia";
import { ref } from "vue";

export const useHeroStore = defineStore({
    id: "heroStore",
    state: () => ({
        lvl: 0,
        profession: '',

    }),
    actions: {

        setLvl(lvl: number) {
            this.lvl = lvl;
        },
        getLvl() {
            return this.lvl;
        },

        setProfession(profession: string) {
            this.profession = profession;
        },

        getProfession() {
            return this.profession;
        }

    }
});
