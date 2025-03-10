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
