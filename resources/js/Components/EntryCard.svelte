<script lang="ts">
    import type {Entry} from "../types";

    let { entry }: { entry: Entry } = $props();
</script>

<div class="entry-card p-4 m-2 border border-gray-300 rounded shadow-sm">
    <div class="entry-header mb-2">
        <div class="text-xl font-bold">{entry.word}</div>
        <div class="text-sm text-gray-600 italic">{entry.pinyin}</div>
    </div>

    {#if entry.definitions && entry.definitions.length > 0}
        <div class="definitions mt-2">
            {#each entry.definitions as definition (definition.id)}
                <div class="definition mt-2 pl-2 border-l-2 border-gray-300">
                    <div class="font-semibold">{definition.part}</div>
                    <div class="pl-2">{definition.definition}</div>

                    {#if definition.examples && definition.examples.length > 0}
                        <div class="examples mt-1 pl-4">
                            {#each definition.examples as example (example.id)}
                                <div class="example text-sm text-gray-700">• {example.sentence}</div>
                            {/each}
                        </div>
                    {/if}
                </div>
            {/each}
        </div>
    {/if}
</div>
