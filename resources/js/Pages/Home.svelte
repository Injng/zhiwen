<script lang="ts">
    import { onMount } from "svelte";
    import type { CanvasSelection } from "../types";

    /** API key for OCR purposes. */
    let { key } = $props();

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
                        model: "google/gemini-2.0-flash-lite-preview-02-05:free",
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

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(
                    `OpenRouter API error: ${errorData.error?.message || response.statusText}`,
                );
            }

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
    let canvas: HTMLCanvasElement;

    /** The canvas drawing context. */
    let ctx: CanvasRenderingContext2D;

    /** The canvas offset to account for video controls, in pixels. */
    const canvasOffset = 44;

    /** The user's selection data. */
    let selection: CanvasSelection = {
        x: 0,
        y: 0,
        width: 0,
        height: 0,
        isSelecting: false,
        isSelected: false,
    };

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
        selection = { ...selection, width, height };
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
        selection = { ...selection, width, height, isSelecting, isSelected };
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

    /** Bound to the current vidoe element on the screen. */
    let video: HTMLVideoElement;

    /**
     * Captures a frame from the current playing video and outputs a blob object.
     */
    async function captureSelection() {
        // ensure a selection has been made
        if (!selection.isSelected) return null;

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

        // calculate acutal displayed width and height
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
        video.pause();

        // convert to blob and pass to ocr
        const blob = dataURLToBlob(captureCanvas.toDataURL("image/png"));
        ocrText = await ocr(blob);
        // console.log(URL.createObjectURL(blob));
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
        return new Blob([u8arr], { type: mime });
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
            let blob = new Blob([buffer], { type: file.type });
            let url = URL.createObjectURL(blob);
            let video = document.querySelector("video") as HTMLVideoElement;
            video.src = url;
        };
    }

    onMount(() => {
        // initialize canvas
        ctx = canvas.getContext("2d");
        resizeCanvas();
        window.addEventListener("resize", resizeCanvas);
    });
</script>

<div class="grid grid-cols-[1fr_3fr] h-screen">
    <div class="grid grid-rows-[3fr_1fr]">
        <div>1</div>
        <div>
            <input
                class="m-2 p-2 outline outline-black hover:bg-black hover:text-white"
                type="file"
                onchange={loadVideo}
            />
            <button
                class="m-2 p-2 outline outline-black hover:bg-black hover:text-white"
                onclick={captureSelection}
            >
                Capture
            </button>
        </div>
    </div>
    <div class="grid grid-rows-[3fr_1fr]">
        <div class="relative">
            <video
                class="w-full h-full object-contain"
                controls
                bind:this={video}
                onloadedmetadata={resizeCanvas}
            >
                <track kind="captions" src="" label="Chinese" default />
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
                ><span class="invisible">Selection Area</span></button
            >
        </div>
        <div>
            <p class="p-5">{ocrText}</p>
        </div>
    </div>
</div>
