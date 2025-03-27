<script lang="ts">
    import type {Definition, Entry} from "../types";
    import axios from "axios";
    import {onMount} from "svelte";

    let {entries, selectedText = $bindable(), key, model, showBaidu, onUpdate, onClose}: {
        entries: Entry[],
        selectedText: string,
        key: string,
        model: string,
        showBaidu: boolean,
        onUpdate: () => void,
        onClose: () => void
    } = $props();

    /** Values to submit a new example. */
    let exampleValues = $state({
        definition_id: 0,
        sentence: "",
    });

    /** Values to submit a new definition. */
    let definitionValues = $state({
        entry_id: 0,
        part: "",
        definition: "",
    });

    /** Values to submit a new entry. */
    let entryValues = $state({
        word: selectedText,
        pinyin: "",
    });

    function wordChange(e: Event) {
        selectedText = (e.target as HTMLInputElement).value;
        baiduSrc = selectedText.length > 1 ? `https://hanyu.baidu.com/hanyu-page/term/detail?wd=${selectedText}`
            : `https://hanyu.baidu.com/hanyu-page/zici/s?wd=${selectedText}`;
        console.log("changed")
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

    async function generateExample(entry: Entry, definition: Definition) {
        // prepare the API request to OpenRouter
        const response = await fetch(
            "https://openrouter.ai/api/v1/chat/completions",
            {
                method: "POST",
                headers: {
                    Authorization: `Bearer ${key}`,
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    model,
                    messages: [
                        {
                            role: "user",
                            content: [
                                {
                                    type: "text",
                                    text: `Generate an example sentence for the word '${entry.word}', pronounced '${entry.pinyin}', for the definition '${definition.definition}'. Return only the example sentence, no additional comments.`,
                                },
                            ],
                        },
                    ],
                }),
            },
        );

        // process and update the example sentence
        const data = await response.json();
        exampleValues.sentence = data.choices[0].message.content.replace(entry.word, "~");
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

    /** Baidu Hanyu URL for reference. */
    let baiduSrc = $state(selectedText.length > 1 ? `https://hanyu.baidu.com/hanyu-page/term/detail?wd=${selectedText}`
        : `https://hanyu.baidu.com/hanyu-page/zici/s?wd=${selectedText}`);
    $effect(() => {
    });

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
    <div bind:this={modalContainer}
         class="bg-white p-8 rounded-lg shadow-xl m-10 overflow-y-auto max-h-[90vh] {showBaidu ? 'w-full' : 'w-1/3'} min-w-[400px]">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-gray-800">Add New Entry</h2>
        </div>

        <hr class="my-4 border-gray-200"/>

        <div class="{showBaidu ? 'grid grid-cols-[1fr_3fr] gap-6' : ''}">
            <div>
                {#each entries as entry (entry.id)}
                    <!-- Entry Header -->
                    <div class="mb-4 p-3 bg-gray-50 rounded-md">
                        <div class="text-xl font-bold text-gray-800 chinese-text">{entry.word}</div>
                        <div class="text-sm text-gray-600 italic">{entry.pinyin}</div>
                    </div>

                    <!-- Entry Definitions -->
                    <div class="mt-4 space-y-4">
                        {#if entry.definitions && entry.definitions.length > 0}
                            {#each entry.definitions as definition (definition.id)}
                                <div class="p-3 border-l-4 border-blue-400 bg-blue-50 rounded-r-md">
                                    <div class="font-semibold text-gray-800 chinese-text">{definition.part}</div>
                                    <div class="pl-2 text-gray-700 my-2 chinese-text">{definition.definition}</div>

                                    {#if definition.examples && definition.examples.length > 0}
                                        <div class="mt-2 pl-4 space-y-1 chinese-text">
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
                                            <input class="flex-grow px-3 py-2 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 chinese-text"
                                                   id="sentence-{definition.id}"
                                                   bind:value={exampleValues.sentence}
                                                   placeholder="Add example sentence"/>
                                            <button
                                                    type="button"
                                                    onclick={() => {generateExample(entry, definition)}}
                                                    class="px-3 py-2 bg-purple-500 text-white font-medium hover:bg-purple-600 transition-colors focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 flex items-center"
                                                    aria-label="Generate"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1"
                                                     viewBox="0 0 20 20"
                                                     fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                          d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"
                                                          clip-rule="evenodd"/>
                                                </svg>
                                            </button>
                                            <button
                                                    type="submit"
                                                    class="px-4 py-2 bg-blue-500 text-white font-medium rounded-r-md hover:bg-blue-600 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                                            >
                                                Add
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            {/each}
                        {/if}

                        <!-- Add Definition Form -->
                        <div class="mt-6 p-4 border border-dashed border-gray-300 rounded-md">
                            <h3 class="text-lg font-medium text-gray-700 mb-2">Add New Definition</h3>
                            <form onsubmit={(e) => {
                                                e.preventDefault();
                                                newDefinition(entry.id);
                                            }}>
                                <div class="space-y-3">
                                    <div>
                                        <label for="part-{entry.id}" class="block text-sm font-medium text-gray-700">Part
                                            of
                                            Speech</label>
                                        <input class="mt-1 px-3 py-2 w-full border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 chinese-text"
                                               id="part-{entry.id}"
                                               bind:value={definitionValues.part}
                                               placeholder="e.g. 名 (noun), 动 (verb)"/>
                                    </div>
                                    <div>
                                        <label for="definition-{entry.id}"
                                               class="block text-sm font-medium text-gray-700">Definition</label>
                                        <textarea
                                                class="mt-1 px-3 py-2 w-full border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 chinese-text"
                                                id="definition-{entry.id}"
                                                bind:value={definitionValues.definition}
                                                rows="3"
                                                placeholder="Enter definition"></textarea>
                                    </div>
                                    <div class="flex justify-end">
                                        <button
                                                type="submit"
                                                class="px-4 py-2 bg-blue-500 text-white font-medium rounded-md hover:bg-blue-600 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                                        >
                                            Add Definition
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                {/each}

                <hr class="my-6 border-gray-200"/>

                <!-- Add Entry Form -->
                <div class="p-4 border border-dashed border-gray-300 rounded-md bg-gray-50">
                    <h3 class="text-lg font-medium text-gray-700 mb-2">Create New Word Entry</h3>
                    <form class="space-y-4" onsubmit={newEntry}>
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="word">Chinese Character</label>
                            <input bind:value={entryValues.word}
                                   oninput={wordChange}
                                   class="mt-1 px-3 py-2 w-full border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 chinese-text"
                                   id="word"
                                   placeholder="汉字"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="pinyin">Pinyin</label>
                            <input bind:value={entryValues.pinyin}
                                   class="mt-1 px-3 py-2 w-full border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   id="pinyin"
                                   placeholder="hàn zì"/>
                        </div>
                        <div class="flex justify-end">
                            <button
                                    class="px-4 py-2 bg-green-600 text-white font-medium rounded-md hover:bg-green-700 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50"
                                    type="submit"
                            >
                                Create Entry
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {#if showBaidu}
                <!-- Baidu Hanyu for Reference -->
                <div class="pl-4 border-l border-gray-200">
                    <h3 class="text-lg font-medium text-gray-700 mb-3">Reference Dictionary</h3>
                    <div class="h-96 rounded-lg overflow-hidden border border-gray-300 shadow-sm">
                        <div class="relative w-full h-full overflow-hidden">
                            {#key baiduSrc}
                                <iframe
                                        src={baiduSrc}
                                        class="absolute top-0 left-0 w-[133%] h-[133%]"
                                        style="transform: scale(0.75); transform-origin: 0 0;"
                                        title="Baidu Hanyu Dictionary"
                                        loading="lazy">
                                </iframe>
                            {/key}
                        </div>
                    </div>
                </div>
            {/if}
        </div>
    </div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@400;700&display=swap');

    .chinese-text {
        font-family: 'Noto Serif SC', serif;
    }
</style>

