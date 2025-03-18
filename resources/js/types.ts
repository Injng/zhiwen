export type CanvasSelection = {
    x: number;
    y: number;
    width: number;
    height: number;
    isSelecting: boolean;
    isSelected: boolean;
};

export type Example = {
    id: number;
    sentence: string;
}

export type Definition = {
    id: number;
    part: string;
    definition: string;
    examples: Example[];
}

export type Entry = {
    id: number;
    word: string;
    pinyin: string;
    definitions: Definition[];
};

export type Review = {
    id: number;
    entry_id: number;
    type: string;
    due: Date;
    stability: number;
    difficulty: number;
    elapsed_days: number;
    scheduled_days: number;
    reps: number;
    lapses: number;
    state: string;
    last_review: Date;
}

export type TranscriptItem = {
    startTime: number;
    endTime: number;
    text: string;
};

export type SettingValues = {
    model: string;
    captureOnPause: boolean;
    freezeTranscript: boolean;
    transcriptSize: number;
}

export interface Queue {
    enqueue: (item: any) => void;
    dequeue: () => any | undefined;
    isEmpty: () => boolean;
    size: () => number;
}
