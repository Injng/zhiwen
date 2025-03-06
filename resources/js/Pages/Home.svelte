<script lang="ts">
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
        </div>
    </div>
    <div class="grid grid-rows-[3fr_1fr]">
        <div>
            <video class="w-full h-full object-contain" controls>
                <track kind="captions" src="" label="Chinese" default />
            </video>
        </div>
        <div>4</div>
    </div>
</div>
