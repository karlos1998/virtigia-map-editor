import { ref } from "vue"
import axios from "axios"
import { route } from "ziggy-js"
import { QuestResource } from "@/Resources/Quest.resource"

export function useQuestStepSelection() {
    const questNodes = ref<{ key: string, label: string, children?: any[], leaf?: boolean, loading?: boolean }[]>([])
    const loading = ref(false)

    const loadQuests = async () => {
        loading.value = true
        try {
            const { data } = await axios.get<QuestResource[]>(route("quests.search"))
            questNodes.value = data.map(quest => ({
                key: `q-${quest.id}`,
                label: quest.name,
                leaf: false,
                loading: false
            }))
        } catch (error) {
            console.error("Error loading quests:", error)
        } finally {
            loading.value = false
        }
    }

    const loadQuestStepById = async (stepId: number) => {
        loading.value = true
        try {
            const { data } = await axios.get(route("quest.steps.show", { step: stepId }))
            const step = data.step
            const questId = step.quest_id

            // First, make sure the quest is loaded
            if (!questNodes.value.some(q => q.key === `q-${questId}`)) {
                const questResponse = await axios.get<QuestResource[]>(route("quests.search"))
                const quest = questResponse.data.find(q => q.id === questId)
                if (quest) {
                    questNodes.value.push({
                        key: `q-${quest.id}`,
                        label: quest.name,
                        leaf: false,
                        loading: false
                    })
                }
            }

            // Find the quest node
            const questNode = questNodes.value.find(q => q.key === `q-${questId}`)
            if (questNode) {
                // Load the steps for this quest if not already loaded
                if (!questNode.children) {
                    const stepsResponse = await axios.get(route("quests.steps", { quest: questId }))
                    questNode.children = stepsResponse.data.steps.map((s: any) => ({
                        key: `s-${s.id}`,
                        label: s.name,
                        leaf: true,
                        questId: questId,
                        stepId: s.id
                    }))
                }

                // Make sure the step is in the children
                if (!questNode.children?.some(s => s.key === `s-${stepId}`)) {
                    questNode.children?.push({
                        key: `s-${stepId}`,
                        label: step.name,
                        leaf: true,
                        questId: questId,
                        stepId: stepId
                    })
                }
            }
        } catch (error) {
            console.error("Error loading quest step:", error)
        } finally {
            loading.value = false
        }
    }

    const onQuestNodeExpand = async (node: any) => {
        if (!node.children && !node.leaf) {
            node.loading = true
            try {
                // Extract quest ID from the key (format: "q-{id}")
                const questId = node.key.substring(2)
                const { data } = await axios.get(route("quests.steps", { quest: questId }))

                node.children = data.steps.map((step: any) => ({
                    key: `s-${step.id}`,
                    label: step.name,
                    leaf: true,
                    questId: questId,
                    stepId: step.id
                }))
            } catch (error) {
                console.error("Error loading quest steps:", error)
            } finally {
                node.loading = false
            }
        }
    }

    return {
        questNodes,
        loading,
        loadQuests,
        loadQuestStepById,
        onQuestNodeExpand
    }
}
