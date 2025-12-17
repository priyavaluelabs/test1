import { loadConnectAndInitialize } from "@stripe/connect-js";

/**
 * Mount a single Stripe Dashboard component
 */
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

    container.innerHTML = ""; // Clear previous component
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
                    fontWeightMedium: "600",
                    buttonPaddingY: "10px",
                    buttonPaddingX: "16px",
                    colorText: "#111827",
                    colorBackground: "#ffffff",
                    colorBorder: "#e5e7eb",
                },

                rules: {
                    ".Button": {
                        height: "44px",
                        lineHeight: "44px",
                        borderRadius: "8px",
                        border: "1px solid #e5e7eb",
                    },
                    ".Button--primary": {
                        backgroundColor: "#b6111c",
                        color: "#fff",
                    },
                    ".Input": {
                        height: "44px",
                        borderRadius: "6px",
                        border: "1px solid #e5e7eb",
                        padding: "10px 16px",
                    },
                },
            },
        });

        const component = stripeConnect.create(type);
        container.appendChild(component);

        // Function to hide loader when iframe loads
        const hideLoader = (iframe) => {
            if (!iframe) return;
            if (iframe.complete) {
                if (loader) loader.style.display = "none";
                return;
            }
            iframe.addEventListener("load", () => {
                if (loader) loader.style.display = "none";
            });
        };

        // Check first iframe immediately
        const iframe = container.querySelector("iframe");
        if (iframe) {
            hideLoader(iframe);
        } else {
            // Fallback: observe DOM mutations for dynamically added iframe
            const observer = new MutationObserver((mutations, obs) => {
                const iframe = container.querySelector("iframe");
                if (iframe) {
                    hideLoader(iframe);
                    obs.disconnect();
                }
            });
            observer.observe(container, { childList: true, subtree: true });
        }
    } catch (error) {
        console.error("Stripe Dashboard error:", error);
        if (loader) loader.style.display = "none";
    }
};

/**
 * Initialize all Stripe dashboards on the page
 * Supports multiple containers with data-settings
 */
function initStripeDashboards() {
    const containers = document.querySelectorAll("[data-settings]");

    containers.forEach((container) => {
        try {
            const settings = JSON.parse(container.dataset.settings);
            window.mountStripeDashboard(settings);
        } catch (err) {
            console.error("Error parsing Stripe dashboard settings:", err);
        }
    });
}

// Initialize on DOMContentLoaded
document.addEventListener("DOMContentLoaded", initStripeDashboards);
