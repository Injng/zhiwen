<script lang="ts">
    import type {Entry} from "../types";
    import axios from "axios";
    import {onMount} from "svelte";

    let {entries, onUpdate, onClose}: { entries: Entry[], onUpdate: () => void, onClose: () => void } = $props();

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
        word: "",
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

<div class="fixed inset-0 bg-black/50 flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg shadow-lg m-10 overflow-y-auto max-h-[90vh] w-1/3" bind:this={modalContainer}>
        <div>Add New Entry</div>
        <hr class="my-5"/>
        {#each entries as entry (entry.id)}
            <!-- Entry Header -->
            <div class="mb-2">
                <div class="text-xl font-bold">{entry.word}</div>
                <div class="text-sm text-gray-600 italic">{entry.pinyin}</div>
            </div>

            <!-- Entry Definitions -->
            {#if entry.definitions}
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

                            <!-- Add Example Form -->
                            <form onsubmit={(e) => {
                                e.preventDefault();
                                newExample(definition.id);
                            }}>
                                <div class="grid grid-cols-[5fr_1fr]">
                                    <input class="outline outline-black my-2 px-1"
                                           id="sentence-{definition.id}" bind:value={exampleValues.sentence}/>
                                    <button type="submit">+</button>
                                </div>
                            </form>
                        </div>
                    {/each}

                    <!-- Add Definition Form -->
                    <form onsubmit={(e) => {
                                e.preventDefault();
                                newDefinition(entry.id);
                            }}>
                        <div>
                            <input class="outline outline-black my-2 mr-2 px-1 w-[5rem]"
                                   id="part-{entry.id}" bind:value={definitionValues.part} placeholder="名"/>
                            <div class="grid grid-cols-[5fr_1fr]">
                                <textarea class="outline outline-black my-2 px-1"
                                          id="definition-{entry.id}"
                                          bind:value={definitionValues.definition}></textarea>
                                <button type="submit">+</button>
                            </div>
                        </div>
                    </form>
                </div>
            {/if}

            <hr class="my-5"/>
        {/each}

        <!-- Add Entry Form -->
        <form onsubmit={newEntry}>
            <input class="outline outline-black my-2 mr-2 px-1 w-[5rem]"
                   id="entry" bind:value={entryValues.word} placeholder="字"/>
            <input class="outline outline-black my-2 mr-2 px-1 w-[5rem]"
                   id="entry" bind:value={entryValues.pinyin} placeholder="zì"/>
            <button type="submit">+</button>
        </form>
    </div>
</div>
