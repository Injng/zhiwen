<script lang="ts">
    import {onMount} from "svelte";
    import type {SettingValues} from "../types";

    let {settings = $bindable(), onClose}: {
        settings: SettingValues,
        onClose: () => void
    } = $props();

    /** Bound to the popup. */
    let modalContainer: HTMLDivElement;

    /** Available model options */
    const modelOptions = [
        { value: "google/gemini-2.0-flash-lite-preview-02-05:free", label: "Gemini 2.0 Flash Lite" },
        { value: "qwen/qwq-32b:free", label: "Qwen 32B" },
        { value: "meta-llama/llama-3.3-70b-instruct:free", label: "Llama 3.3 70B" },
        { value: "mistralai/mistral-small-24b-instruct-2501:free", label: "Mistral Small 24B" }
    ];

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
    <div bind:this={modalContainer}
         class="bg-white p-8 rounded-lg shadow-xl m-10 overflow-y-auto max-h-[90vh] w-1/3 min-w-[400px]">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-gray-800">Settings</h2>
        </div>

        <hr class="my-4 border-gray-200"/>

        <div class="mb-6">
            <label for="model-select" class="block text-sm font-medium text-gray-700 mb-2">
                AI Model
            </label>
            <div class="relative">
                <select
                        id="model-select"
                        bind:value={settings.model}
                        class="block w-full px-4 py-3 rounded-md border border-gray-300 bg-white shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 text-sm"
                >
                    {#each modelOptions as option}
                        <option value={option.value}>{option.label}</option>
                    {/each}
                </select>
            </div>
            <p class="mt-2 text-sm text-gray-500">
                Choose the AI model for OCR and language processing
            </p>
        </div>

        <div class="mb-6">
            <div class="flex items-center">
                <input
                        id="capture-on-pause"
                        type="checkbox"
                        bind:checked={settings.captureOnPause}
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                >
                <label for="capture-on-pause" class="ml-2 block text-sm font-medium text-gray-700">
                    Auto-capture on pause
                </label>
            </div>
            <p class="mt-2 text-sm text-gray-500 pl-6">
                Automatically capture the selected area when video is paused
            </p>
        </div>

        <div class="mb-6">
            <div class="flex items-center">
                <input
                        id="capture-on-pause"
                        type="checkbox"
                        bind:checked={settings.freezeTranscript}
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                >
                <label for="capture-on-pause" class="ml-2 block text-sm font-medium text-gray-700">
                    Freeze transcript
                </label>
            </div>
            <p class="mt-2 text-sm text-gray-500 pl-6">
                If there is no new text at the time, freeze the current transcript text
            </p>
        </div>

        <div class="mb-6">
            <label for="transcript-size" class="block text-sm font-medium text-gray-700 mb-2">
                Transcript Font Size
            </label>
            <div class="flex items-center space-x-3">
                <input
                        id="transcript-size"
                        type="range"
                        min="10"
                        max="64"
                        step="1"
                        bind:value={settings.transcriptSize}
                        class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                >
                <span class="text-sm font-medium text-gray-700">{settings.transcriptSize}px</span>
            </div>
            <p class="mt-2 text-sm text-gray-500">
                Adjust the font size of the transcript text
            </p>
        </div>
    </div>
</div>