<script lang="ts">
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
        <div>
            <video
                class="w-full h-full object-contain"
                controls
                bind:this={video}
            >
                <track kind="captions" src="" label="Chinese" default />
            </video>
        </div>
        <div>4</div>
    </div>
</div>
