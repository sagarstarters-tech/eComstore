// ajax_update_shipping.js

/**
 * Modern Vanilla JS implementation to hook cart totals 
 * cleanly up with the PHP shipping controller API without React.
 */
document.addEventListener('DOMContentLoaded', () => {

    const cartTotalNode = document.getElementById('base_cart_total');

    // We bind to whenever a cart triggers an 'update'
    // E.g qty changes, deleted item, coupon runs
    document.addEventListener('cart_updated', function (e) {

        let subtotal = 0;

        if (e.detail && e.detail.cart_total) {
            subtotal = parseFloat(e.detail.cart_total);
        } else if (cartTotalNode) {
            // Fallback to DOM
            subtotal = parseFloat(cartTotalNode.dataset.value || 0);
        }

        recalculateShippingTotals(subtotal);
    });

    /**
     * Hit the ShippingController backend securely to fetch 
     * precise structural JSON response.
     */
    function recalculateShippingTotals(subtotal) {

        const formData = new FormData();
        formData.append('cart_total', subtotal);

        // Make AJAX request natively
        fetch('/shipping_module_src/src/Controllers/ShippingController.php?action=calculate', {
            method: 'POST',
            body: formData,
            headers: {
                // Ensure proper standard identifying 
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error("HTTP connection to backend failed with status " + response.status);
                }
                return response.json();
            })
            .then(json => {

                if (json.status === 'success') {
                    const results = json.data;
                    const metadata = results.shipping_metadata;

                    // Update UI visually
                    updateShippingUI(results.shipping_cost, results.grand_total, metadata.message, metadata.is_free);
                } else {
                    console.error("Shipping Engine Error:", json.message);
                }

            })
            .catch(error => {
                console.error("Shipping Network Failure:", error);
            });
    }

    /**
     * Re-renders the right-side summary box specifically
     */
    function updateShippingUI(shippingCost, finalTotal, message, isFree) {

        const shippingCostNode = document.getElementById('shipping_cost_display');
        const grandTotalNode = document.getElementById('grand_total_display');
        const shippingMessageNode = document.getElementById('shipping_message_prompt');

        if (shippingCostNode) {
            shippingCostNode.innerText = isFree ? "Free" : "₹" + parseFloat(shippingCost).toFixed(2);
            shippingCostNode.style.color = isFree ? "#28a745" : "inherit"; // Green if free
        }

        if (grandTotalNode) {
            grandTotalNode.innerText = "₹" + parseFloat(finalTotal).toFixed(2);
        }

        if (shippingMessageNode) {
            shippingMessageNode.innerText = message;
            // Dynamic UI styling (Green for eligible, yellow/blue for not yet eligible)
            shippingMessageNode.className = isFree ? 'alert alert-success p-2 small mt-2' : 'alert alert-info p-2 small mt-2';
        }
    }
});
