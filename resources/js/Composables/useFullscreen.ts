import { ref, onMounted, onBeforeUnmount, type Ref } from "vue";

export function useFullscreen(stageRef: Ref<HTMLElement | null>) {
    const isFullscreen = ref(false);

    let originalOverflow = "";
    let resizeObserver: ResizeObserver | null = null;

    const isIOS =
        /iPad|iPhone|iPod/.test(navigator.userAgent) ||
        (navigator.platform === "MacIntel" && navigator.maxTouchPoints > 1);

    /* ----------------------------------------------------
     * Helpers
     * -------------------------------------------------- */

    const lockOrientation = async () => {
        try {
            if (screen.orientation?.lock) {
                await screen.orientation.lock("landscape");
            }
        } catch {
            /* iOS Safari ignora */
        }
    };

    const unlockOrientation = async () => {
        try {
            if (screen.orientation?.unlock) {
                screen.orientation.unlock();
            }
        } catch {}
    };

    const applyBodyFixes = () => {
        originalOverflow = document.body.style.overflow;
        document.body.style.overflow = "hidden";

        // iOS viewport bug fix
        if (isIOS) {
            document.documentElement.style.height = "100dvh";
            document.body.style.height = "100dvh";
        }
    };

    const removeBodyFixes = () => {
        document.body.style.overflow = originalOverflow || "";

        if (isIOS) {
            document.documentElement.style.height = "";
            document.body.style.height = "";
        }
    };

    const resizeStage = () => {
        const el = stageRef.value;
        if (!el) return;

        el.style.width = "100vw";
        el.style.height = "100vh";
    };

    const resetStageSize = () => {
        const el = stageRef.value;
        if (!el) return;

        el.style.width = "";
        el.style.height = "";
    };

    /* ----------------------------------------------------
     * Fullscreen control
     * -------------------------------------------------- */

    const enterFullscreen = async () => {
        const el = stageRef.value;
        if (!el || document.fullscreenElement) return;

        await el.requestFullscreen();
    };

    const exitFullscreen = async () => {
        if (!document.fullscreenElement) return;
        await document.exitFullscreen();
    };

    const toggleFullscreen = () => {
        isFullscreen.value ? exitFullscreen() : enterFullscreen();
    };

    /* ----------------------------------------------------
     * Events
     * -------------------------------------------------- */

    const handleFullscreenChange = async () => {
        isFullscreen.value = !!document.fullscreenElement;

        if (isFullscreen.value) {
            applyBodyFixes();
            resizeStage();
            await lockOrientation();
        } else {
            removeBodyFixes();
            resetStageSize();
            await unlockOrientation();
        }
    };

    const handleOrientationChange = () => {
        // auto fullscreen ao girar para landscape
        if (window.matchMedia("(orientation: landscape)").matches) {
            if (!isFullscreen.value) {
                enterFullscreen();
            }
        } else {
            if (isFullscreen.value) {
                exitFullscreen();
            }
        }
    };

    const handleResize = () => {
        if (isFullscreen.value) {
            resizeStage();
        }
    };

    /* ----------------------------------------------------
     * Lifecycle
     * -------------------------------------------------- */

    onMounted(() => {
        document.addEventListener("fullscreenchange", handleFullscreenChange);

        window.addEventListener("orientationchange", handleOrientationChange);

        window.addEventListener("resize", handleResize);

        if (stageRef.value) {
            resizeObserver = new ResizeObserver(handleResize);
            resizeObserver.observe(stageRef.value);
        }
    });

    onBeforeUnmount(() => {
        document.removeEventListener(
            "fullscreenchange",
            handleFullscreenChange
        );

        window.removeEventListener(
            "orientationchange",
            handleOrientationChange
        );

        window.removeEventListener("resize", handleResize);

        resizeObserver?.disconnect();
        resizeObserver = null;
    });

    return {
        isFullscreen,
        enterFullscreen,
        exitFullscreen,
        toggleFullscreen,
    };
}
