<script lang="ts">
    import type {Definition, Entry, Example, Review} from "../types";
    import type {Card, FSRS, FSRSParameters, Grade, RecordLogItem} from "ts-fsrs";
    import {fsrs, generatorParameters, Rating} from "ts-fsrs";
    import {reviewToCard, updateReview} from "../srs";
    import axios from "axios";
    import {onMount} from "svelte";

    /** The current review card that is being reviewed. */
    let toReview: Review | null = null;

    /** The current entry being reviewed. */
    let currentEntry: Entry | null = null;

    /** Whether to show the answer. */
    let showAnswer = false;

    /** Whether the entry has updated to show the card. */
    let showCard = false;

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
        showCard = true;
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
                showCard = false;
                updateQueue();
                showAnswer = false;
            });
    }

    /**
     * Handles keybinds for the review page in order to enable faster review
     * @param e The keyboard event that triggered the keybind.
     */
    function handleKeybinds(e: KeyboardEvent) {
        // skip if any modifier keys are pressed to prevent interfering with browser shortcuts
        if (e.ctrlKey || e.altKey || e.metaKey || e.shiftKey) return;

        // toggle the front and back of the card
        else if (e.code === "Space") {
            showAnswer = !showAnswer;
            e.preventDefault();
        }

        // rate the card as "again"
        else if (e.code === "Digit1") {
            if (showAnswer) rateCard(Rating.Again);
            e.preventDefault();
        }

        // rate the card as "hard"
        else if (e.code === "Digit2") {
            if (showAnswer) rateCard(Rating.Hard);
            e.preventDefault();
        }

        // rate the card as "good"
        else if (e.code === "Digit3") {
            if (showAnswer) rateCard(Rating.Good);
            e.preventDefault();
        }

        // rate the card as "easy"
        else if (e.code === "Digit4") {
            if (showAnswer) rateCard(Rating.Easy);
            e.preventDefault();
        }
    }

    // initialize
    updateQueue();

    onMount(() => {
        // add event listener to handle keybinds
        document.addEventListener("keydown", handleKeybinds);

        // remove event listener on component destroy
        return () => {
            document.removeEventListener("keydown", handleKeybinds);
        };
    });
</script>

<div class="w-full max-w-2xl mx-auto">
    {#if toReview === null}
        <div class="bg-white p-8 rounded-lg shadow-md text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            <h2 class="text-xl font-semibold text-gray-800 mb-2">No Cards to Review</h2>
            <p class="text-gray-600 mb-6">You've completed all your reviews for now. Check back later!</p>
            <p class="text-sm text-gray-500">To add new cards, select text in the dictionary and click the "+" button</p>
        </div>
    {:else if currentEntry}
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            {#if showCard}
                <!-- Card header with progress info -->
                <div class="bg-blue-50 px-6 py-4 border-b border-blue-100">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">Vocabulary Review</h2>
                            <p class="text-sm text-gray-600">Card state: <span class="font-medium">{toReview.state}</span></p>
                        </div>
                        <div class="text-sm text-gray-500">
                            <span class="font-medium">Reps:</span> {toReview.reps} |
                            <span class="font-medium">Lapses:</span> {toReview.lapses}
                        </div>
                    </div>
                </div>

                <!-- Card content -->
                <div class="p-6">
                    {#if toReview.type === "word"}
                        <!-- Word Card -->
                        {#if !showAnswer}
                            <!-- Front of card (question) -->
                            <div class="text-center py-10">
                                <h2 class="text-4xl font-bold text-gray-800 mb-6 chinese-text">{currentEntry.word}</h2>
                                <button
                                    class="px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    onclick={() => showAnswer = true}
                                >
                                    Show Answer
                                </button>
                            </div>
                        {:else}
                            <!-- Back of card (answer) -->
                            <div class="mb-8">
                                <div class="flex justify-between items-center mb-4">
                                    <h2 class="text-3xl font-bold text-gray-800 chinese-text">{currentEntry.word}</h2>
                                    <div class="text-xl text-gray-600 font-medium">{currentEntry.pinyin}</div>
                                </div>

                                {#if currentEntry.definitions && currentEntry.definitions.length > 0}
                                    <div class="space-y-4 mt-5 chinese-text">
                                        {#each currentEntry.definitions as definition (definition.id)}
                                            <div class="p-3 border-l-4 border-blue-400 bg-blue-50 rounded-r-md">
                                                <div class="font-semibold text-gray-800">{definition.part}</div>
                                                <div class="pl-2 text-gray-700 my-2">{definition.definition}</div>

                                                {#if definition.examples && definition.examples.length > 0}
                                                    <div class="mt-2 pl-4 space-y-2">
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

                            <!-- Rating buttons -->
                            <div class="grid grid-cols-4 gap-3 mt-6">
                                <button
                                    class="px-4 py-3 bg-red-500 hover:bg-red-600 text-white font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 flex flex-col items-center"
                                    onclick={() => rateCard(Rating.Again)}
                                >
                                    <span class="text-lg font-bold">Again</span>
                                </button>
                                <button
                                    class="px-4 py-3 bg-orange-400 hover:bg-orange-500 text-white font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2 flex flex-col items-center"
                                    onclick={() => rateCard(Rating.Hard)}
                                >
                                    <span class="text-lg font-bold">Hard</span>
                                </button>
                                <button
                                    class="px-4 py-3 bg-green-500 hover:bg-green-600 text-white font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 flex flex-col items-center"
                                    onclick={() => rateCard(Rating.Good)}
                                >
                                    <span class="text-lg font-bold">Good</span>
                                </button>
                                <button
                                    class="px-4 py-3 bg-blue-500 hover:bg-blue-600 text-white font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 flex flex-col items-center"
                                    onclick={() => rateCard(Rating.Easy)}
                                >
                                    <span class="text-lg font-bold">Easy</span>
                                </button>
                            </div>
                        {/if}
                    {:else}
                        <!-- Cloze Card -->
                        {#if !showAnswer}
                            <!-- Front of card (question) -->
                            <div class="py-8">
                                {#if currentEntry.definitions && currentEntry.definitions.some(def => def.examples && def.examples.length > 0)}
                                    {#key currentEntry.id}
                                        {@const allExamples = currentEntry.definitions.flatMap(def => def.examples || [])}
                                        {@const randomExample = allExamples[Math.floor(Math.random() * allExamples.length)]}
                                        {@const clozeText = randomExample?.sentence.includes('~')
                                            ? randomExample.sentence.replace('~', '<span class="px-4 py-1 mx-1 border-b-2 border-blue-500 inline-block min-w-[4rem] bg-blue-50 rounded">_____</span>')
                                            : randomExample?.sentence.replace(currentEntry.word, '<span class="px-4 py-1 mx-1 border-b-2 border-blue-500 inline-block min-w-[4rem] bg-blue-50 rounded">_____</span>')}

                                        <div class="mb-6">
                                            <div class="text-base text-gray-500 mb-2 text-center">{currentEntry.pinyin}</div>
                                            <p class="text-xl text-gray-800 leading-relaxed mb-6 text-center chinese-text">
                                                {@html clozeText}
                                            </p>
                                        </div>
                                    {/key}
                                {:else}
                                    <div class="text-center text-gray-500 mb-6">No example sentences available</div>
                                {/if}

                                <div class="text-center">
                                    <button
                                            class="px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                            onclick={() => showAnswer = true}
                                    >
                                        Show Answer
                                    </button>
                                </div>
                            </div>
                        {:else}
                            <!-- Back of card (answer) -->
                            <div class="mb-8">
                                <div class="flex justify-between items-center mb-4">
                                    <h2 class="text-3xl font-bold text-gray-800 chinese-text">{currentEntry.word}</h2>
                                    <div class="text-xl text-gray-600 font-medium">{currentEntry.pinyin}</div>
                                </div>

                                <div class="chinese-text">
                                    {#if currentEntry.definitions && currentEntry.definitions.some(def => def.examples && def.examples.length > 0)}
                                        {#key currentEntry.id}
                                            {@const allExamples = currentEntry.definitions.flatMap(def => def.examples || [])}
                                            {@const randomExample = allExamples[Math.floor(Math.random() * allExamples.length)]}
                                            {@const highlightedText = randomExample?.sentence.includes('~')
                                                ? randomExample.sentence.replace('~', `<span class="text-blue-600 font-bold bg-blue-50 px-1 rounded">${currentEntry.word}</span>`)
                                                : randomExample?.sentence.replace(currentEntry.word, `<span class="text-blue-600 font-bold bg-blue-50 px-1 rounded">${currentEntry.word}</span>`)}

                                            <div class="py-3 px-4 bg-blue-50 rounded-md mb-4">
                                                <p class="text-lg text-gray-800">
                                                    {@html highlightedText}
                                                </p>
                                            </div>
                                        {/key}
                                    {/if}

                                    {#if currentEntry.definitions && currentEntry.definitions.length > 0}
                                        <div class="space-y-4 mt-5">
                                            {#each currentEntry.definitions as definition (definition.id)}
                                                <div class="p-3 border-l-4 border-blue-400 bg-blue-50 rounded-r-md">
                                                    <div class="font-semibold text-gray-800">{definition.part}</div>
                                                    <div class="pl-2 text-gray-700 my-2">{definition.definition}</div>
                                                </div>
                                            {/each}
                                        </div>
                                    {/if}
                                </div>
                            </div>

                            <!-- Rating buttons -->
                            <div class="grid grid-cols-4 gap-3 mt-6">
                                <button
                                        class="px-4 py-3 bg-red-500 hover:bg-red-600 text-white font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 flex flex-col items-center"
                                        onclick={() => rateCard(Rating.Again)}
                                >
                                    <span class="text-lg font-bold">Again</span>
                                </button>
                                <button
                                        class="px-4 py-3 bg-orange-400 hover:bg-orange-500 text-white font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2 flex flex-col items-center"
                                        onclick={() => rateCard(Rating.Hard)}
                                >
                                    <span class="text-lg font-bold">Hard</span>
                                </button>
                                <button
                                        class="px-4 py-3 bg-green-500 hover:bg-green-600 text-white font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 flex flex-col items-center"
                                        onclick={() => rateCard(Rating.Good)}
                                >
                                    <span class="text-lg font-bold">Good</span>
                                </button>
                                <button
                                        class="px-4 py-3 bg-blue-500 hover:bg-blue-600 text-white font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 flex flex-col items-center"
                                        onclick={() => rateCard(Rating.Easy)}
                                >
                                    <span class="text-lg font-bold">Easy</span>
                                </button>
                            </div>
                        {/if}
                    {/if}
                </div>
            {/if}
        </div>
    {:else}
        <div class="bg-white p-8 rounded-lg shadow-md text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-red-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <h2 class="text-xl font-semibold text-gray-800 mb-2">Error Loading Card</h2>
            <p class="text-gray-600">There was a problem loading the review card. Please try again.</p>
        </div>
    {/if}
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@400;700&display=swap');

    .chinese-text {
        font-family: 'Noto Serif SC', serif;
    }
</style>
