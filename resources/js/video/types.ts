import { Ref } from "vue";

export type VideoSourceType = "panda" | "youtube" | "vimeo" | "mp4";

export interface VideoAdapter {
    mount(): Promise<void>;
    play(): void;
    pause(): void;
    seek(time: number): void;
    destroy(): void;
    unmute?: () => void;
    mute?: () => void;
    setVolume?: (volume: number) => void;
    setSpeed?: (rate: number) => void;
    state: {
        currentTime: Ref<number>;
        duration: Ref<number>;
        isPlaying: Ref<boolean>;
    };
}

export interface UseVideoPlayerOptions {
    containerRef: Ref<HTMLElement | null>;
    videoUrl: string;

    autoplay?: boolean;
    muted?: boolean;

    initialRate?: number;
    // 🔥 O "Cordão Umbilical": Uma função que resgata o valor fresco do Vue
    getPlaybackRate?: () => number;
    onRateChange?: (rate: number) => void;

    onTimeUpdate?: (time: number) => void;
    onReady?: () => void;
}
