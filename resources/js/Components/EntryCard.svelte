<script lang="ts">
    import type {Entry, Review} from "../types";
    import type {Card} from "ts-fsrs";
    import {createEmptyCard, fsrs, generatorParameters, State} from "ts-fsrs";
    import {stateToString} from "../srs";
    import axios from "axios";

    let {entry}: { entry: Entry } = $props();

    /** Parameters for the FSRS algorithm. */
    const params = generatorParameters();

    /** FSRS algorithm for reviewing words. */
    const srs = fsrs(params);

    /** Values to submit a new card. */
    let cardValues: Review = {
        id: null,
        entry_id: entry.id,
        due: new Date(),
        stability: 0.0,
        difficulty: 0.0,
        elapsed_days: 0,
        scheduled_days: 0,
        reps: 0,
        lapses: 0,
        state: "new",
        last_review: null,
    }

    /**
     * Add a new card to the database.
     */
    async function addCard() {
        // create new card
        let card: Card = createEmptyCard();

        // map values in card to cardValues
        cardValues.entry_id = entry.id;
        cardValues.due = card.due;
        cardValues.stability = card.stability;
        cardValues.difficulty = card.difficulty;
        cardValues.elapsed_days = card.elapsed_days;
        cardValues.scheduled_days = card.scheduled_days;
        cardValues.reps = card.reps;
        cardValues.lapses = card.lapses;
        cardValues.state = stateToString(card.state);
        cardValues.last_review = card.last_review;

        // add card to the database
        await axios.post("/cards/new", cardValues)
            .then(() => {
                // update state for existing card
                exists();
            });
    }

    /** State for if a card already exists for this entry. */
    let cardExists = $state(false);

    /**
     * Check if a card exists in the database for this entry.
     */
    async function exists() {
        // check if a card exists in the database and update state
        await axios.get(`/cards/find/entry/${entry.id}`)
            .then((response) => {
                const data = response.data as Review[];
                cardExists = response.data.length != 0;
            });
    }
    exists();
</script>

<div class="p-4 m-2 border border-gray-300 rounded shadow-sm">
    <div class="mb-2 flex justify-between items-start">
        <div>
            <div class="text-xl font-bold">{entry.word}</div>
            <div class="text-sm text-gray-600 italic">{entry.pinyin}</div>
        </div>
        <button class="w-6 h-6 flex items-center justify-center rounded-full transition-colors"
                class:bg-gray-100={cardExists}
                class:bg-gray-200={!cardExists}
                class:cursor-not-allowed={cardExists}
                class:hover:bg-gray-300={!cardExists}
                disabled={cardExists}
                onclick={addCard}
                title={cardExists ? "Card already exists" : "Add card"}>
            <span class="font-bold"
                  class:text-gray-400={cardExists}
                  class:text-gray-700={!cardExists}>+</span>
        </button>
    </div>

    {#if entry.definitions && entry.definitions.length > 0}
        <div class="mt-2">
            {#each entry.definitions as definition (definition.id)}
                <div class="mt-2 pl-2 border-l-2 border-gray-300">
                    <div class="font-semibold">{definition.part}</div>
                    <div class="pl-2">{definition.definition}</div>

                    {#if definition.examples && definition.examples.length > 0}
                        <div class="mt-1 pl-4">
                            {#each definition.examples as example (example.id)}
                                <div class="text-sm text-gray-700">• {example.sentence}</div>
                            {/each}
                        </div>
                    {/if}
                </div>
            {/each}
        </div>
    {/if}
</div>
