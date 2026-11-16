let pandaScriptLoaded = false;

export const loadPandaScript = (): Promise<void> => {
    return new Promise((resolve) => {
        if (pandaScriptLoaded || (window as any).PandaPlayer) {
            pandaScriptLoaded = true;
            return resolve();
        }

        const script = document.createElement("script");
        script.src = "https://player.pandavideo.com/player.js";
        script.async = true;
        script.onload = () => {
            pandaScriptLoaded = true;
            resolve();
        };

        document.head.appendChild(script);
    });
};

export const createPandaPlayer = (
    container: HTMLElement,
    videoId: string,
    callbacks: {
        onReady?: (duration: number) => void;
        onTimeUpdate?: (time: number) => void;
        onPlay?: () => void;
        onPause?: () => void;
    }
) => {
    // 1️⃣ cria o elemento que o Panda espera
    const pandaDiv = document.createElement("div");
    pandaDiv.setAttribute("data-panda-player", videoId);
    pandaDiv.style.width = "100%";
    pandaDiv.style.height = "100%";

    container.appendChild(pandaDiv);

    const PandaPlayer = (window as any).PandaPlayer;
    const player = new PandaPlayer(pandaDiv, {
        videoId,
        autoplay: true,
    });

    // 2️⃣ eventos
    player.onEvent((event: any) => {
        switch (event.type) {
            case "panda_ready":
                callbacks.onReady?.(player.getDuration());
                break;

            case "panda_timeupdate":
                callbacks.onTimeUpdate?.(event.currentTime);
                break;

            case "panda_play":
                callbacks.onPlay?.();
                break;

            case "panda_pause":
                callbacks.onPause?.();
                break;
        }
    });

    // 3️⃣ adapter compatível com seu editor
    return {
        play: () => player.play(),
        pause: () => player.pause(),
        togglePlay: () => player.togglePlay(),
        destroy: () => {
            player.destroy();
            container.innerHTML = "";
        },
        set currentTime(t: number) {
            player.setCurrentTime(t);
        },
        get currentTime() {
            return player.getCurrentTime();
        },
        get duration() {
            return player.getDuration();
        },
        get paused() {
            return player.isPaused();
        },
    };
};
