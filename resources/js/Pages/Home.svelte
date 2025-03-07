<script lang="ts">
    import { onMount } from "svelte";
    import type { CanvasSelection } from "../types";

    /** Bound to the canvas element that the user draws on. */
    let canvas: HTMLCanvasElement;

    /** The canvas drawing context. */
    let ctx: CanvasRenderingContext2D;

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
        const width = e.clientX - selection.x - rect.left;
        const height = e.clientY - selection.y - rect.top;

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
        const width = e.clientX - selection.x - rect.left;
        const height = e.clientY - selection.y - rect.top;

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
    function captureFrame() {
        // create canvas for drawing image
        const canvas = document.createElement("canvas");
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        // create canvas context and draw image from video
        const ctx = canvas.getContext("2d");
        ctx.drawImage(video, 0, 0);

        // convert to blob and return
        const blob = dataURLToBlob(canvas.toDataURL("image/png"));
        // console.log(URL.createObjectURL(blob));
        return blob;
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
                on:change={loadVideo}
            />
            <button
                class="m-2 p-2 outline outline-black hover:bg-black hover:text-white"
                on:click={captureFrame}
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
                on:loadedmetadata={resizeCanvas}
            >
                <track kind="captions" src="" label="Chinese" default />
            </video>
            <canvas
                class="absolute top-0 left-0 w-full h-full pointer-events-auto cursor-crosshair"
                bind:this={canvas}
                on:mousedown={selectionStart}
                on:mousemove={selectionMove}
                on:mouseup={selectionEnd}
            ></canvas>
        </div>
        <div>4</div>
    </div>
</div>
