import { loadConnectAndInitialize } from "@stripe/connect-js";

window.mountStripeDashboard = async function ({
    publishableKey,
    clientSecret,
    type,
    containerId,
    loaderId
}) {
    const container = document.getElementById(containerId);
    const loader = document.getElementById(loaderId);

    if (!container) {
        console.error("Stripe container not found:", containerId);
        return;
    }

    // Reset UI
    container.innerHTML = "";
    if (loader) loader.style.display = "flex";

    try {
        const stripeConnect = await loadConnectAndInitialize({
            publishableKey,
            fetchClientSecret: async () => clientSecret,
            appearance: {
                theme: "stripe",
                overlays: "dialog",
                variables: {
                    colorPrimary: "#b6111c",
                    borderRadius: "8px",
                    spacingUnit: "10px",
                },
            },
        });

        const component = stripeConnect.create(type);
        container.appendChild(component);

        /**
         * Hide loader once iframe loads
         */
        const waitForIframeLoad = () => {
            const iframe = container.querySelector("iframe");

            if (iframe) {
                iframe.addEventListener("load", () => {
                    if (loader) loader.style.display = "none";
                });

                // Safety: iframe might already be loaded
                if (iframe.complete) {
                    if (loader) loader.style.display = "none";
                }

                return true;
            }
            return false;
        };

        // Try immediately
        if (!waitForIframeLoad()) {
            // Observe DOM until iframe appears
            const observer = new MutationObserver(() => {
                if (waitForIframeLoad()) {
                    observer.disconnect();
                }
            });

            observer.observe(container, { childList: true, subtree: true });

            // ⛑️ Fallback timeout (important!)
            setTimeout(() => {
                observer.disconnect();
                if (loader) loader.style.display = "none";
            }, 15000);
        }
    } catch (error) {
        console.error("Stripe Dashboard error:", error);
        if (loader) loader.style.display = "none";
    }
};
