import { ref } from "vue"
import axios from "axios"
import { route } from "ziggy-js"
import { QuestResource } from "@/Resources/Quest.resource"

export type QuestStepNode = {
    key: string
    label: string
    leaf: true
    questId: number
    stepId: number
}

export type QuestNode = {
    key: string
    label: string
    children?: QuestStepNode[]
    leaf: false
    loading: boolean
    questId: number
}

type QuestSearchResource = QuestResource & {
    steps?: {
        id: number
        quest_id: number
        name: string
    }[]
}

const questNodes = ref<QuestNode[]>([])
const loading = ref(false)
let loadedWithSteps = false
let pendingLoad: Promise<void> | null = null
let pendingLoadIncludesSteps = false

const mapQuestToNode = (quest: QuestSearchResource): QuestNode => ({
    key: `q-${quest.id}`,
    label: quest.name,
    leaf: false,
    loading: false,
    questId: quest.id,
    children: quest.steps?.map(step => ({
        key: `s-${step.id}`,
        label: step.name,
        leaf: true,
        questId: quest.id,
        stepId: step.id
    }))
})

export function useQuestStepSelection() {
    const loadQuests = async (options: { withSteps?: boolean, force?: boolean } = {}) => {
        if (!options.force && questNodes.value.length > 0 && (!options.withSteps || loadedWithSteps)) {
            return
        }

        if (pendingLoad && (!options.withSteps || pendingLoadIncludesSteps)) {
            return pendingLoad
        }

        if (pendingLoad && options.withSteps && !pendingLoadIncludesSteps) {
            return pendingLoad.finally(() => loadQuests({ ...options, force: true }))
        }

        loading.value = true
        pendingLoadIncludesSteps = Boolean(options.withSteps)
        pendingLoad = axios
            .get<QuestSearchResource[]>(route("quests.search"), {
                params: {
                    with_steps: options.withSteps ? 1 : undefined
                }
            })
            .then(({ data }) => {
                questNodes.value = data.map(mapQuestToNode)
                loadedWithSteps = Boolean(options.withSteps)
            })
            .catch((error) => {
                console.error("Error loading quests:", error)
            })
            .finally(() => {
                loading.value = false
                pendingLoad = null
                pendingLoadIncludesSteps = false
            })

        return pendingLoad
    }

    const loadQuestStepById = async (stepId: number) => {
        const existingQuest = questNodes.value.find(quest =>
            quest.children?.some(step => step.stepId === stepId)
        )

        if (existingQuest) {
            return
        }

        loading.value = true
        try {
            const { data } = await axios.get(route("quest.steps.show", { step: stepId }))
            const step = data.step
            const questId = step.quest_id

            // First, make sure the quest is loaded
            if (!questNodes.value.some(q => q.key === `q-${questId}`)) {
                const questResponse = await axios.get<QuestSearchResource[]>(route("quests.search"))
                const quest = questResponse.data.find(q => q.id === questId)
                if (quest) {
                    questNodes.value.push({
                        key: `q-${quest.id}`,
                        label: quest.name,
                        leaf: false,
                        loading: false,
                        questId: quest.id
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
                        questId: Number(questId),
                        stepId: s.id
                    }))
                }

                // Make sure the step is in the children
                if (!questNode.children?.some(s => s.key === `s-${stepId}`)) {
                    questNode.children?.push({
                        key: `s-${stepId}`,
                        label: step.name,
                        leaf: true,
                        questId: Number(questId),
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
                    questId: Number(questId),
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
