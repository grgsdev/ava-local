let googleMapsLoading: Promise<void> | null = null;

export function loadGoogleMaps(apiKey: string): Promise<void> {
    if ((window as any).google && (window as any).google.maps) {
        // Já está carregado
        return Promise.resolve();
    }

    if (googleMapsLoading) {
        // Já está em processo de carregamento
        return googleMapsLoading;
    }

    googleMapsLoading = new Promise((resolve, reject) => {
        if (!apiKey) {
            reject(new Error("Google Maps API Key não encontrada."));
            return;
        }

        const script = document.createElement("script");
        script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places`;
        script.async = true;
        script.defer = true;

        script.onload = () => {
            resolve();
        };

        script.onerror = () => {
            reject(new Error("Falha ao carregar Google Maps API."));
        };

        document.head.appendChild(script);
    });

    return googleMapsLoading;
}
