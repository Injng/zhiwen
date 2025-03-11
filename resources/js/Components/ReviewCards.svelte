<script lang="ts">
    import type {Definition, Entry, Example, Review} from "../types";
    import type {Card, FSRS, FSRSParameters, Grade, RecordLogItem} from "ts-fsrs";
    import {fsrs, generatorParameters, Rating} from "ts-fsrs";
    import {reviewToCard, updateReview} from "../srs";
    import axios from "axios";

    /** The current review card that is being reviewed. */
    let toReview: Review | null = null;

    /** The current entry being reviewed. */
    let currentEntry: Entry | null = null;

    /** Whether to show the answer. */
    let showAnswer = false;

    /** The FSRS parameters. */
    const params: FSRSParameters = generatorParameters();

    /** The FSRS algorithm. */
    const srs: FSRS = fsrs(params);

    /**
     * Update the queue of review cards.
     */
    async function updateQueue() {
        // get the queue of review cards
        const response = await fetch("/cards/due");
        if (!response.ok) return;
        let data = await response.json();
        if (data.message) {
            toReview = null;
            currentEntry = null;
            return;
        }
        toReview = data as Review;

        // get the current entry being reviewed if there are cards
        await getEntry(toReview.entry_id);
    }

    /**
     * Get an entry by its ID.
     * @param entry_id The entry ID to get.
     */
    async function getEntry(entry_id: number) {
        // get the entry by its ID
        const response = await fetch(`/entry/${entry_id}`);
        if (!response.ok) return;
        currentEntry = await response.json() as Entry;

        // get the definitions for the entry
        const definitions = await fetch(`/definitions/${entry_id}`);
        if (!definitions.ok) return;

        // update the entry with definitions
        currentEntry.definitions = await definitions.json() as Definition[];
        if (!currentEntry.definitions || currentEntry.definitions.length === 0) return;

        // get examples for each definition
        for (let definition of currentEntry.definitions) {
            // fetch examples
            const response = await fetch(`/examples/${definition.id}`);
            if (!response.ok) continue;

            // update definition with examples
            definition.examples = await response.json() as Example[];
        }
    }

    /**
     * Updates the current card with the user rating and updates the queue.
     * @param rating The user rating for the card.
     */
    async function rateCard(rating: Grade) {
        // update card data
        const currentCard: Card = reviewToCard(toReview);
        const log: RecordLogItem = srs.next(currentCard, new Date(), rating);
        const newReview: Review = updateReview(toReview, log.card);

        // update card in the database
        await axios.post(`/cards/${newReview.id}`, newReview)
            .then(() => {
                // update the queue
                updateQueue();
                showAnswer = false;
            });
    }

    // initialize
    updateQueue();
</script>

<div>
    <h1>Review Cards</h1>
    <div>
        {#if toReview === null}
            <p>No cards to review.</p>
        {:else if currentEntry}
            <div>
                {#if !showAnswer}
                    <button onclick={() => showAnswer = true}>{currentEntry.word}</button>
                {:else}
                    <div>{currentEntry.pinyin}</div>
                    {#if currentEntry.definitions && currentEntry.definitions.length > 0}
                        <div class="mt-2">
                            {#each currentEntry.definitions as definition (definition.id)}
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
                    <div class="grid grid-cols-4">
                        <button onclick={() => rateCard(Rating.Again)}>Again</button>
                        <button onclick={() => rateCard(Rating.Hard)}>Hard</button>
                        <button onclick={() => rateCard(Rating.Good)}>Good</button>
                        <button onclick={() => rateCard(Rating.Easy)}>Easy</button>
                    </div>
                {/if}
            </div>
        {:else}
            <p>Error loading entry</p>
        {/if}
    </div>
</div>
