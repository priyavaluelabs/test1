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

    const initialHeight = container.offsetHeight;

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
         * Hide loader when container height increases
         */
        const hideLoader = () => {
            if (loader) loader.style.display = "none";
        };

        const resizeObserver = new ResizeObserver(entries => {
            for (const entry of entries) {
                const newHeight = entry.contentRect.height;

                if (newHeight > initialHeight + 50) {
                    hideLoader();
                    resizeObserver.disconnect();
                }
            }
        });

        resizeObserver.observe(container);

        // ⛑️ Safety fallback
        setTimeout(() => {
            resizeObserver.disconnect();
            hideLoader();
        }, 15000);

    } catch (error) {
        console.error("Stripe Dashboard error:", error);
        if (loader) loader.style.display = "none";
    }
};
