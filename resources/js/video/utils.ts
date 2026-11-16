import { VideoSourceType } from "./types";

// resources/js/video/utils.ts
export const loadPandaScript = (): Promise<void> =>
    new Promise((resolve) => {
        if ((window as any).PandaPlayer) return resolve();
        (window as any).pandascripttag = (window as any).pandascripttag || [];
        const script = document.createElement("script");
        script.src = "https://player.pandavideo.com.br/api.v2.js";
        script.async = true;
        script.onload = () => resolve();
        document.head.appendChild(script);
    });

export const getVideoSource = (
    url: string
): { type: VideoSourceType; src: string } => {
    if (!url) return { type: "mp4", src: "" };

    if (url.includes("pandavideo.com.br") || url.includes("b-vz")) {
        return { type: "panda", src: url };
    }

    // ✅ VIMEO EMBED
    const vimeoEmbed = url.match(/player\.vimeo\.com\/video\/(\d+)/);
    if (vimeoEmbed) {
        return { type: "vimeo", src: vimeoEmbed[1] };
    }

    // ✅ VIMEO PUBLIC
    const vimeoPublic = url.match(/vimeo\.com\/(\d+)/);
    if (vimeoPublic) {
        return { type: "vimeo", src: vimeoPublic[1] };
    }

    const yt = url.match(/(?:youtube\.com\/.*v=|youtu\.be\/)([^"&?/ ]{11})/);
    if (yt) return { type: "youtube", src: yt[1] };

    return { type: "mp4", src: url };
};

export function normalizeVimeoSource(url: string): string | number {
    // https://player.vimeo.com/video/1131687758
    const embedMatch = url.match(/player\.vimeo\.com\/video\/(\d+)/);
    if (embedMatch) return embedMatch[1];

    // https://vimeo.com/1131687758
    const publicMatch = url.match(/vimeo\.com\/(\d+)/);
    if (publicMatch) return publicMatch[1];

    return url;
}

export function extractVimeoId(url: string): string | null {
    const match = url.match(/vimeo\.com\/(?:video\/)?(\d+)/);
    return match ? match[1] : null;
}

// Panda URL normalization
export function normalizePandaUrl(input: string): string {
    if (!input) return input;

    if (input.includes("/embed/?v=")) return input;

    const idMatch = input.match(
        /([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i
    );
    if (!idMatch) return input;

    const domainMatch = input.match(
        /(https:\/\/player-[^\/]+\.tv\.pandavideo\.com\.br)/i
    );
    if (!domainMatch) return input;

    return `${domainMatch[1]}/embed/?v=${idMatch[1]}`;
}

// export function normalizePandaUrl(input: string): string {
//     if (!input) return "";

//     // 1. Já está no formato embed → retorna direto
//     if (input.includes("/embed/?v=")) {
//         return input;
//     }

//     // 2. Extrair o UUID do vídeo
//     const idMatch = input.match(
//         /([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i
//     );

//     if (!idMatch) {
//         console.error("❌ Panda: vídeo ID não encontrado na URL", input);
//         return "";
//     }

//     const videoId = idMatch[1];

//     // 3. Extrair domínio do player (player-vz-xxxx.tv.pandavideo.com.br)
//     const domainMatch = input.match(
//         /(https:\/\/player-[^\/]+\.tv\.pandavideo\.com\.br)/i
//     );

//     if (!domainMatch) {
//         console.error("❌ Panda: domínio do player não encontrado", input);
//         return "";
//     }

//     const domain = domainMatch[1];

//     // 4. URL FINAL CORRETA
//     return `${domain}/embed/?v=${videoId}`;
// }
