<script lang="ts">
    import {onMount} from "svelte";
    import type {CanvasSelection, Definition, Entry, Example, SettingValues, TranscriptItem} from "../types";
    import EntryCard from "../Components/EntryCard.svelte";
    import NewEntry from "../Components/NewEntry.svelte";
    import ReviewCards from "../Components/ReviewCards.svelte";
    import Settings from "../Components/Settings.svelte";

    /** API key for OCR purposes. */
    let {key} = $props();

    /**
     * State for keeping track of which page the user is on.
     * 1: Home, 2: Review
     */
    let pageState = $state(1);

    /** Application settings. */
    let settings: SettingValues = $state({
        model: "google/gemini-2.0-flash-lite-preview-02-05:free",
        captureOnPause: false,
        freezeTranscript: true,
        transcriptSize: 24,
        showBaidu: false,
    });

    /** State for showing the settings dialog. */
    let showSettings = $state(false);

    /** Toggles the settings dialog between open and close. */
    function toggleSettings() {
        showSettings = !showSettings;
    }

    /** State for showing the new entry dialog. */
    let showNewEntry = $state(false);

    /** Toggles the new entry dialog between open and close. */
    function toggleNew() {
        showNewEntry = !showNewEntry;
    }

    let transcript: TranscriptItem[] = $state([]);
    let previousTranscriptText = $state("");
    let currentTranscriptText = $state("");
    let hasTranscript = $state(false);

    /**
     * Parses an SRT file and loads the transcript data.
     * @param e The event object containing the file input element
     */
    function loadTranscript(e: Event) {
        const target = e.target as HTMLInputElement;
        const file = target.files?.[0];

        if (!file) {
            console.error("No file selected");
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            const content = e.target?.result as string;
            transcript = parseSRT(content);
            hasTranscript = transcript.length > 0;
        };
        reader.readAsText(file);
    }

    /**
     * Parses SRT format into transcript items.
     * @param srtContent The SRT content as string
     */
    function parseSRT(srtContent: string): TranscriptItem[] {
        // split transcript into blocks
        const items: TranscriptItem[] = [];
        const blocks = srtContent.trim().split(/\n\s*\n/);

        // iterate through the blocks
        for (const block of blocks) {
            // split the block into the id, timestamp, and text components
            const lines = block.split('\n');
            if (lines.length < 3) continue;

            // match the timestamp and the text
            const times = lines[1].match(/(\d{2}:\d{2}:\d{2},\d{3}) --> (\d{2}:\d{2}:\d{2},\d{3})/);
            if (!times) continue;

            // convert timestamps to seconds and text
            const startTime = timeToSeconds(times[1]);
            const endTime = timeToSeconds(times[2]);
            const text = lines.slice(2).join('\n');

            // add to items
            items.push({startTime, endTime, text});
        }
        return items;
    }

    /**
     * Converts SRT timestamp to seconds.
     * @param timeString The time string in format "00:00:00,000"
     */
    function timeToSeconds(timeString: string): number {
        const [hours, minutes, rest] = timeString.split(':');
        const [seconds, milliseconds] = rest.split(',');

        return parseInt(hours) * 3600 +
            parseInt(minutes) * 60 +
            parseInt(seconds) +
            parseInt(milliseconds) / 1000;
    }

    /**
     * Updates the current transcript based on video time.
     */
    function updateTranscript() {
        // check if video and transcript are available
        if (!video || !hasTranscript) return;

        // get current time and find the current transcript item
        const currentTime = video.currentTime;
        const current = transcript.find(
            item => currentTime >= item.startTime && currentTime <= item.endTime
        );

        // if the current transcript text is already equal to the current text, return
        if (current && current.text === currentTranscriptText) return;

        // otherwise, update the current and previous transcript text
        if (current) {
            previousTranscriptText = currentTranscriptText;
            currentTranscriptText = current.text;
        } else if (!settings.freezeTranscript) {
            previousTranscriptText = currentTranscriptText;
            currentTranscriptText = "";
        }
    }

    /** Bound to the div element that contains the transcribed element. */
    let transcription: HTMLDivElement = $state(null);

    /** Bound to the div element that contains the dictionary entries. */
    let dictionary: HTMLDivElement = $state(null);

    /** Text that the user has currently selected within the transcription element. */
    let selectedText = $state("");

    /** Current entries to display in the dictionary. */
    let entries: Entry[] = $state([]);

    /**
     * For each entry in entries, get the definitions and examples and update the entry object.
     */
    async function getDefinitions() {
        // iterate through each entry
        for (let entry of entries) {
            // fetch definitions
            const response = await fetch(`/definitions/${entry.id}`);
            if (!response.ok) continue;

            // update entry with definitions
            entry.definitions = await response.json() as Definition[];
            if (!entry.definitions || entry.definitions.length === 0) continue;

            // get examples for each definition
            for (let definition of entry.definitions) {
                // fetch examples
                const response = await fetch(`/examples/${definition.id}`);
                if (!response.ok) continue;

                // update definition with examples
                definition.examples = await response.json() as Example[];
            }
        }
    }

    /**
     * Fetches dictionary entries for the selected text and updates the entries state.
     */
    async function getEntries() {
        // attempt to obtain the dictionary entry for the selected text
        const response = await fetch(`/entries/${selectedText}`);
        if (!response.ok) return;

        // obtain entries and update state
        const data = await response.json() as Entry[];
        entries = data.map((entry) => ({
            ...entry,
            definitions: []
        }));

        await getDefinitions();
    }

    /**
     * Handles user selection of text and updates only if the selection is of the transcribed text.
     */
    function handleSelectionChange() {
        // get selection and check if it exists
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) return;
        if (!transcription) return;

        // if selected text is none, keep current selection
        if (selection.toString().trim() === "") return;

        // ensure that selection is within the transcription or dictionary element
        const range = selection.getRangeAt(0);
        const container = range.commonAncestorContainer;
        if (!transcription.contains(container) && !dictionary.contains(container)) return;

        // update selected text
        selectedText = range.toString().trim();
    }

    /** Text extracted from the image using OCR. */
    let ocrText = $state("");

    /**
     * Runs OCR on image by passing it to a vision-based model.
     * @param img The image to run OCR on.
     */
    async function ocr(img: Blob) {
        try {
            // convert image blob to base64
            const base64Img = await blobToBase64(img);

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
                        model: settings.model,
                        messages: [
                            {
                                role: "user",
                                content: [
                                    {
                                        type: "text",
                                        text: "Extract all Chinese text visible in this image. Return only the extracted text, no additional comments. If there is no text, return 'NO_TEXT_FOUND'.",
                                    },
                                    {
                                        type: "image_url",
                                        image_url: {
                                            url: base64Img,
                                            detail: "high",
                                        },
                                    },
                                ],
                            },
                        ],
                    }),
                },
            );

            const data = await response.json();
            return data.choices[0].message.content;
        } catch (error) {
            console.error("Error sending image to OpenRouter:", error);
            throw error;
        }
    }

    /**
     * Function to convert a blob to base64 data.
     * @param blob The blob to convert.
     */
    function blobToBase64(blob: Blob) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onloadend = () => {
                const base64String = reader.result;
                resolve(base64String);
            };
            reader.onerror = reject;
            reader.readAsDataURL(blob);
        });
    }

    /** Bound to the canvas element that the user draws on. */
    let canvas: HTMLCanvasElement = $state(null);

    /** The canvas drawing context. */
    let ctx: CanvasRenderingContext2D;

    /** The canvas offset to account for video controls, in pixels. */
    const canvasOffset = 44;

    /** The user's selection data. */
    let selection: CanvasSelection = $state({
        x: 0,
        y: 0,
        width: 0,
        height: 0,
        isSelecting: false,
        isSelected: false,
    });

    /**
     * Handles the start of the selection on a mouse down event in the canvas.
     * @param e The mouse event that triggered the selection start.
     */
    function selectionStart(e: MouseEvent) {
        const rect = canvas.getBoundingClientRect();
        selection = {
            x: e.clientX - rect.left,
            y: e.clientY - rect.top,
            width: 0,
            height: 0,
            isSelecting: true,
            isSelected: false,
        };
    }

    /**
     * Handles updating the selection and visual rectangle as the user drags the mouse around the canvas.
     * @param e The mouse event that triggered the selection update.
     */
    function selectionMove(e: MouseEvent) {
        // ensure the selection is active
        if (!selection.isSelecting) return;

        // calculate the width and height of the selection
        const rect = canvas.getBoundingClientRect();
        const currentX = e.clientX - rect.left;
        const currentY = e.clientY - rect.top;
        const width = currentX - selection.x;
        const height = currentY - selection.y;

        // update the selection object and draw the selection
        selection = {...selection, width, height};
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.strokeStyle = "red";
        ctx.strokeRect(
            selection.x,
            selection.y,
            selection.width,
            selection.height,
        );
    }

    /**
     * Handles the end of the selection after a mouse up event by finalizing the selection.
     * @param e The mouse event that triggered the selection end.
     */
    function selectionEnd(e: MouseEvent) {
        // ensure the selection is active
        if (!selection.isSelecting) return;

        // calculate the width and height of the selection
        const rect = canvas.getBoundingClientRect();
        const currentX = e.clientX - rect.left;
        const currentY = e.clientY - rect.top;
        const width = currentX - selection.x;
        const height = currentY - selection.y;

        // update the selection object and clears the canvas
        const isSelecting = false;
        const isSelected = true;
        selection = {...selection, width, height, isSelecting, isSelected};
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }

    /**
     * Resize the canvas to match the video display size
     */
    function resizeCanvas() {
        if (!canvas || !video) return;

        const videoContainer = canvas.parentElement;
        if (videoContainer) {
            canvas.width = videoContainer.clientWidth;
            canvas.height = videoContainer.clientHeight;
        }
    }

    /** Bound to the current video element on the screen. */
    let video: HTMLVideoElement = $state(null);

    /** The blob URL of the loaded video. */
    let videoSrc = $state("");

    /** The video element's timestamp. */
    let videoTime = $state(0);

    // update video source when it changes
    $effect(() => {
        // set video source and time when it changes
        if (video && videoSrc != "") {
            video.src = videoSrc;
            video.currentTime = videoTime;
        }
    })

    /** The delay to wait for the video to play before initiating capture. */
    const captureDelay = 150;

    /**
     * Captures a frame from the current playing video and outputs a blob object.
     */
    async function captureSelection(fromPaused: boolean) {
        // disable if captureOnPause is disabled
        if (!settings.captureOnPause && fromPaused) return;

        // ensure a selection has been made
        if (!selection.isSelected || !video) return null;

        // if video is paused, play and set delay to ensure successful capture
        if (video.paused && !fromPaused) {
            await video.play();
            await new Promise((resolve) => setTimeout(resolve, captureDelay));
        }

        // create canvas for drawing image
        const captureCanvas = document.createElement("canvas");

        // get coordinates for selection (account for direction of selection)
        const selStartX = Math.min(selection.x, selection.x + selection.width);
        const selStartY = Math.min(selection.y, selection.y + selection.height);
        const selWidth = Math.abs(selection.width);
        const selHeight = Math.abs(selection.height);

        // set canvas to the selection size
        captureCanvas.width = selWidth;
        captureCanvas.height = selHeight;

        // create context for the capture canvas
        const captureCtx = captureCanvas.getContext("2d");
        if (!captureCtx) {
            console.error("Could not get canvas context");
            return null;
        }

        // calculate dimensions of video height
        const videoRect = video.getBoundingClientRect();
        const visibleVideoHeight = videoRect.height;

        // calculate the actual display dimensions of the video (accounting for object-contain)
        const videoRatio = video.videoWidth / video.videoHeight;
        const containerRatio = videoRect.width / visibleVideoHeight;

        // calculate actual displayed width and height
        let displayedWidth: number, displayedHeight: number;
        if (videoRatio > containerRatio) {
            displayedWidth = videoRect.width;
            displayedHeight = displayedWidth / videoRatio;
        } else {
            displayedHeight = visibleVideoHeight;
            displayedWidth = displayedHeight * videoRatio;
        }

        // calculate the position of the video within its container (centering)
        const videoX = (videoRect.width - displayedWidth) / 2;
        const videoY = (visibleVideoHeight - displayedHeight) / 2;

        // calculate scale factors between displayed video and actual video dimensions
        const scaleX = video.videoWidth / displayedWidth;
        const scaleY = video.videoHeight / displayedHeight;

        // adjust selection coordinates to be relative to the actual video content
        const adjustedX = selStartX - videoX;
        const adjustedY = selStartY - videoY;

        // scale to get the actual position in the source video
        const sourceX = adjustedX * scaleX;
        const sourceY = adjustedY * scaleY;
        const sourceWidth = selWidth * scaleX;
        const sourceHeight = selHeight * scaleY;

        // ensure coordinates are within video bounds
        const clampedX = Math.max(0, sourceX);
        const clampedY = Math.max(0, sourceY);
        const clampedWidth = Math.min(sourceWidth, video.videoWidth - clampedX);
        const clampedHeight = Math.min(
            sourceHeight,
            video.videoHeight - clampedY,
        );

        // draw the selected portion of the video onto the canvas
        captureCtx.drawImage(
            video,
            clampedX,
            clampedY,
            clampedWidth,
            clampedHeight,
            0,
            0,
            selWidth,
            selHeight,
        );

        // pause the video here
        if (!fromPaused) video.pause();

        // convert to blob and pass to ocr
        const blob = dataURLToBlob(captureCanvas.toDataURL("image/png"));
        ocrText = await ocr(blob);
    }

    /**
     * Converts a dataURL to a blob object.
     * @param dataURL the dataURL to convert
     */
    function dataURLToBlob(dataURL: string): Blob {
        const arr = dataURL.split(",");
        const mime = arr[0].match(/:(.*?);/)?.[1];
        const bstr = atob(arr[1]);
        let n = bstr.length;
        const u8arr = new Uint8Array(n);
        while (n--) {
            u8arr[n] = bstr.charCodeAt(n);
        }
        return new Blob([u8arr], {type: mime});
    }

    /**
     * Loads video into the video element using the file reader interface.
     * @param e the event object containing the file input element
     */
    function loadVideo(e: Event) {
        // load file from the input
        const target = e.target as HTMLInputElement;
        const file = target.files?.[0];

        // validate file existence
        if (!file) {
            console.error("No file selected");
            return;
        }

        // validate file type
        if (!file.type.startsWith("video")) {
            console.error("Invalid file type");
            return;
        }

        // initialize file reader
        const reader = new FileReader();
        reader.readAsArrayBuffer(file);

        // set video source
        reader.onload = (e) => {
            let buffer = e.target?.result as ArrayBuffer;
            let blob = new Blob([buffer], {type: file.type});
            videoSrc = URL.createObjectURL(blob);
        };
    }

    /**
     * Handles keybinds for the application in order to enable fast navigation.
     * @param e The keyboard event that triggered the keybind.
     */
    function handleKeybinds(e: KeyboardEvent) {
        // skip if any modifier keys are pressed to prevent interfering with browser shortcuts
        if (e.ctrlKey || e.altKey || e.metaKey || e.shiftKey) return;

        // skip if not on the home screen or if a dialog is open
        if (showNewEntry || showSettings || pageState !== 1) return;

        // skip if the event target is an input, textarea, or other form element
        const target = e.target as HTMLElement;
        if (target.tagName === 'INPUT' ||
            target.tagName === 'TEXTAREA' ||
            target.tagName === 'SELECT' ||
            target.isContentEditable) {
            return;
        }

        // toggle pause on video
        if (e.code === "Space") {
            if (video.paused) video.play();
            else video.pause();
            e.preventDefault();
        }

        // seek forward
        else if (e.code === "ArrowRight") {
            video.currentTime += 5;
        }

        // seek backward
        else if (e.code === "ArrowLeft") {
            video.currentTime -= 5;
        }

        // capture the selected area
        else if (e.code === "KeyC") {
            captureSelection(false);
            e.preventDefault();
        }

        // get entries from dictionary
        else if (e.code === "KeyD") {
            getEntries();
            e.preventDefault();
        }

        // toggle the new entry dialog
        else if (e.code === "KeyN") {
            toggleNew();
            e.preventDefault();
        }

        // go to the review page
        else if (e.code === "KeyR") {
            pageState = 2;
            videoTime = video?.currentTime;
            e.preventDefault();
        }
    }

    onMount(() => {
        // initialize canvas
        ctx = canvas.getContext("2d");
        resizeCanvas();
        window.addEventListener("resize", resizeCanvas);

        // add event listener to track text selection
        document.addEventListener("selectionchange", handleSelectionChange);

        // add event listener to handle keybinds
        document.addEventListener("keydown", handleKeybinds);

        // cleanup
        return () => {
            document.removeEventListener(
                "selectionchange",
                handleSelectionChange,
            );
            window.removeEventListener("resize", resizeCanvas);
        };
    });
</script>

<div class="min-h-screen bg-gray-50">
    {#if pageState === 1}
        <div class="grid grid-cols-1 md:grid-cols-[1fr_3fr] h-screen">
            <div class="flex flex-col border-r border-gray-200 bg-white shadow-sm">
                <!-- Dictionary Entries Section -->
                <div class="flex-grow overflow-y-auto p-2" bind:this={dictionary}>
                    <div class="flex justify-between items-center px-2 py-3 mb-2 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-800">Dictionary</h2>
                        <button
                                class="text-gray-500 hover:text-gray-800 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-full p-1"
                                onclick={toggleSettings}
                                aria-label="Settings"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                 fill="currentColor">
                                <path fill-rule="evenodd"
                                      d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z"
                                      clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                    {#if entries.length > 0}
                        <div class="space-y-3">
                            {#each entries as entry (entry.id)}
                                <EntryCard {entry}/>
                            {/each}
                        </div>
                    {:else}
                        <p class="text-gray-500 text-center py-6">No entries found. Select text or add new entries.</p>
                    {/if}
                </div>

                <!-- Controls Section -->
                <div class="p-3 bg-gray-50 border-t border-gray-200">
                    <div class="mb-3">
                        <label for="video-upload" class="block text-sm font-medium text-gray-700 mb-1">Video/Transcript
                            Upload</label>
                        <div class="grid grid-cols-2 gap-2 items-center">
                            <label class="cursor-pointer flex-grow">
                                <span class="py-2 px-3 bg-white border border-gray-300 rounded-md shadow-sm text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 inline-flex items-center w-full">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-500"
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                    Select Video File
                                </span>
                                <input
                                        id="video-upload"
                                        type="file"
                                        class="hidden"
                                        accept="video/*"
                                        onchange={loadVideo}
                                />
                            </label>
                            <label class="cursor-pointer flex-grow">
                                <span class="py-2 px-3 bg-white border border-gray-300 rounded-md shadow-sm text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 inline-flex items-center w-full">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-500"
                                         fill="none"
                                         viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                    Select SRT File
                                </span>
                                <input
                                        id="transcript-upload"
                                        type="file"
                                        class="hidden"
                                        accept=".srt"
                                        onchange={loadTranscript}
                                />
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <button
                                class="px-4 py-2 bg-blue-500 text-white font-medium rounded-md hover:bg-blue-600 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                onclick={() => captureSelection(false)}
                                title="c"
                        >
                            Capture
                        </button>
                        <button
                                class="px-4 py-2 bg-blue-500 text-white font-medium rounded-md hover:bg-blue-600 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                onclick={getEntries}
                                title="d"
                        >
                            Dictionary
                        </button>
                        <button
                                class="px-4 py-2 bg-green-600 text-white font-medium rounded-md hover:bg-green-700 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                                onclick={toggleNew}
                                title="n"
                        >
                            New Entry
                        </button>
                        <button
                                class="px-4 py-2 bg-gray-700 text-white font-medium rounded-md hover:bg-gray-800 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                                onclick={() => {
                                    pageState = 2;
                                    videoTime = video?.currentTime;
                                }}
                                title="r"
                        >
                            Review
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex flex-col">
                <!-- Video Player Section -->
                <div class="relative flex-grow bg-black">
                    <video
                            class="w-full h-full object-contain"
                            controls
                            bind:this={video}
                            onloadedmetadata={resizeCanvas}
                            ontimeupdate={updateTranscript}
                            onpause={() => captureSelection(true)}
                    >
                        <track kind="captions" src="" label="Chinese" default/>
                    </video>
                    <canvas
                            class="absolute top-0 left-0 w-full h-full pointer-events-none"
                            bind:this={canvas}
                    ></canvas>
                    <button
                            class="absolute top-0 left-0 w-full cursor-crosshair"
                            style="height: calc(100% - {canvasOffset}px);"
                            onmousedown={selectionStart}
                            onmousemove={selectionMove}
                            onmouseup={selectionEnd}
                    ><span class="sr-only">Selection Area</span></button>

                    <!-- Selection info indicator -->
                    {#if selection.isSelected}
                        <div class="absolute top-2 right-2 bg-black/70 text-white px-3 py-1 rounded-md text-sm">
                            Selection active
                        </div>
                    {/if}
                </div>

                <!-- Transcription Section -->
                <div class="h-44 bg-white border-t border-gray-300 overflow-y-auto">
                    <div class="p-4 h-full" bind:this={transcription}>
                        {#if hasTranscript}
                            <h3 class="font-medium text-sm text-gray-500 mb-1">Transcript:</h3>
                            <p class="text-lg text-gray-800">{previousTranscriptText}</p>
                            <p class="text-lg text-gray-800 chinese-text"
                               style="font-size: {settings.transcriptSize}px;">{currentTranscriptText}</p>
                        {:else if ocrText}
                            <h3 class="font-medium text-sm text-gray-500 mb-1">OCR Result:</h3>
                            <p class="text-lg text-gray-800 chinese-text"
                               style="font-size: {settings.transcriptSize}px;">{ocrText}</p>
                        {:else}
                            <p class="text-gray-400 h-full flex items-center justify-center"
                               style="font-size: {settings.transcriptSize}px;">
                                Capture a section of the video to extract text or upload a transcript
                            </p>
                        {/if}
                    </div>
                </div>
            </div>

            {#if showNewEntry}
                <NewEntry {entries} bind:selectedText {key} model={settings.model} showBaidu={settings.showBaidu}
                          onUpdate={getEntries}
                          onClose={toggleNew}/>
            {/if}

            {#if showSettings}
                <Settings bind:settings onClose={toggleSettings}/>
            {/if}
        </div>
    {:else if pageState === 2}
        <div class="p-6 max-w-3xl mx-auto">
            <div class="mb-4 flex items-center">
                <button
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md mr-3 flex items-center transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                        onclick={() => pageState = 1}
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back
                </button>
                <h1 class="text-2xl font-bold text-gray-800">Review Cards</h1>
            </div>
            <div class="bg-white shadow-md rounded-lg p-6">
                <ReviewCards/>
            </div>
        </div>
    {/if}
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@400;700&display=swap');

    .chinese-text {
        font-family: 'Noto Serif SC', serif;
    }
</style>

