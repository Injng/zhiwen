<script lang="ts">
    import type {Entry} from "../types";
    import axios from "axios";
    import {onMount} from "svelte";

    let {entries, selectedText, onUpdate, onClose}: {
        entries: Entry[],
        selectedText: string,
        onUpdate: () => void,
        onClose: () => void
    } = $props();

    /** Values to submit a new example. */
    let exampleValues = {
        definition_id: 0,
        sentence: "",
    }

    /** Values to submit a new definition. */
    let definitionValues = {
        entry_id: 0,
        part: "",
        definition: "",
    }

    /** Values to submit a new entry. */
    let entryValues = {
        word: selectedText,
        pinyin: "",
    }

    /**
     * Submit a new example.
     * @param definition_id The definition ID to add the example to.
     */
    function newExample(definition_id: number) {
        // add definition to the database
        exampleValues.definition_id = definition_id;
        axios.post("/examples/new", exampleValues)
            .then((response) => {
                // update the entries immediately on frontend
                const newExample = response.data;
                for (let entry of entries) {
                    for (let definition of entry.definitions) {
                        if (definition.id === definition_id) {
                            definition.examples.push(newExample);
                            break;
                        }
                    }
                }
                exampleValues.sentence = "";
            });
    }

    /**
     * Submit a new definition.
     * @param entry_id The entry ID to add the definition to.
     */
    function newDefinition(entry_id: number) {
        // add definition to the database
        definitionValues.entry_id = entry_id;
        axios.post("/definitions/new", definitionValues)
            .then(() => {
                // update the entries immediately on frontend
                onUpdate();
            });
    }

    /**
     * Submit a new entry.
     */
    function newEntry(e: Event) {
        // add entry to the database
        e.preventDefault();
        axios.post("/entries/new", entryValues)
            .then(() => {
                // update the entries immediately on frontend
                onUpdate();
            });
    }

    /** Bound to the popup. */
    let modalContainer: HTMLDivElement;

    /** Handles closing the popup when clicking outside of it. */
    function closePopup(e: MouseEvent) {
        if (modalContainer && !modalContainer.contains(e.target as Node)) {
            onClose();
        }
    }

    onMount(() => {
        // close the popup when clicking outside of it
        setTimeout(() => {
            window.addEventListener('click', closePopup);
        }, 100);

        return () => {
            window.removeEventListener("click", closePopup);
        }
    })
</script>

<div class="fixed inset-0 bg-black/60 flex items-center justify-center z-50">
    <div class="bg-white p-8 rounded-lg shadow-xl m-10 overflow-y-auto max-h-[90vh] w-1/3 min-w-[400px]" bind:this={modalContainer}>
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-gray-800">Add New Entry</h2>
            <button
                class="text-gray-500 hover:text-gray-800 transition-colors"
                onclick={onClose}
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <hr class="my-4 border-gray-200"/>

        {#each entries as entry (entry.id)}
            <!-- Entry Header -->
            <div class="mb-4 p-3 bg-gray-50 rounded-md">
                <div class="text-xl font-bold text-gray-800">{entry.word}</div>
                <div class="text-sm text-gray-600 italic">{entry.pinyin}</div>
            </div>

            <!-- Entry Definitions -->
            {#if entry.definitions && entry.definitions.length > 0}
                <div class="mt-4 space-y-4">
                    {#each entry.definitions as definition (definition.id)}
                        <div class="p-3 border-l-4 border-blue-400 bg-blue-50 rounded-r-md">
                            <div class="font-semibold text-gray-800">{definition.part}</div>
                            <div class="pl-2 text-gray-700 my-2">{definition.definition}</div>

                            {#if definition.examples && definition.examples.length > 0}
                                <div class="mt-2 pl-4 space-y-1">
                                    {#each definition.examples as example (example.id)}
                                        <div class="text-sm text-gray-700">• {example.sentence}</div>
                                    {/each}
                                </div>
                            {/if}

                            <!-- Add Example Form -->
                            <form class="mt-3" onsubmit={(e) => {
                                e.preventDefault();
                                newExample(definition.id);
                            }}>
                                <div class="flex">
                                    <input class="flex-grow px-3 py-2 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           id="sentence-{definition.id}"
                                           bind:value={exampleValues.sentence}
                                           placeholder="Add example sentence"/>
                                    <button
                                        type="submit"
                                        class="px-4 py-2 bg-blue-500 text-white font-medium rounded-r-md hover:bg-blue-600 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                                        disabled={!exampleValues.sentence}
                                    >
                                        Add
                                    </button>
                                </div>
                            </form>
                        </div>
                    {/each}

                    <!-- Add Definition Form -->
                    <div class="mt-6 p-4 border border-dashed border-gray-300 rounded-md">
                        <h3 class="text-lg font-medium text-gray-700 mb-2">Add New Definition</h3>
                        <form onsubmit={(e) => {
                                    e.preventDefault();
                                    newDefinition(entry.id);
                                }}>
                            <div class="space-y-3">
                                <div>
                                    <label for="part-{entry.id}" class="block text-sm font-medium text-gray-700">Part of Speech</label>
                                    <input class="mt-1 px-3 py-2 w-full border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           id="part-{entry.id}"
                                           bind:value={definitionValues.part}
                                           placeholder="e.g. 名 (noun), 动 (verb)"/>
                                </div>
                                <div>
                                    <label for="definition-{entry.id}" class="block text-sm font-medium text-gray-700">Definition</label>
                                    <textarea class="mt-1 px-3 py-2 w-full border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                              id="definition-{entry.id}"
                                              bind:value={definitionValues.definition}
                                              rows="3"
                                              placeholder="Enter definition"></textarea>
                                </div>
                                <div class="flex justify-end">
                                    <button
                                        type="submit"
                                        class="px-4 py-2 bg-blue-500 text-white font-medium rounded-md hover:bg-blue-600 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                                        disabled={!definitionValues.part || !definitionValues.definition}
                                    >
                                        Add Definition
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            {/if}
        {/each}

        <hr class="my-6 border-gray-200"/>

        <!-- Add Entry Form -->
        <div class="p-4 border border-dashed border-gray-300 rounded-md bg-gray-50">
            <h3 class="text-lg font-medium text-gray-700 mb-2">Create New Word Entry</h3>
            <form onsubmit={newEntry} class="space-y-4">
                <div>
                    <label for="word" class="block text-sm font-medium text-gray-700">Chinese Character</label>
                    <input class="mt-1 px-3 py-2 w-full border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           id="word"
                           bind:value={entryValues.word}
                           placeholder="汉字"/>
                </div>
                <div>
                    <label for="pinyin" class="block text-sm font-medium text-gray-700">Pinyin</label>
                    <input class="mt-1 px-3 py-2 w-full border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           id="pinyin"
                           bind:value={entryValues.pinyin}
                           placeholder="hàn zì"/>
                </div>
                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="px-4 py-2 bg-green-600 text-white font-medium rounded-md hover:bg-green-700 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50"
                        disabled={!entryValues.word || !entryValues.pinyin}
                    >
                        Create Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
