/**
 * ajax_tracking.js
 * Handles fetching Tracking Details seamlessly without a page reload 
 */

document.addEventListener('DOMContentLoaded', function () {
    const trackingForm = document.getElementById('trackingForm');
    const trackingResultContainer = document.getElementById('trackingResultContainer');

    if (trackingForm) {
        trackingForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const orderId = document.getElementById('track_order_id').value;
            const email = document.getElementById('track_email').value;
            const submitBtn = trackingForm.querySelector('button[type="submit"]');

            submitBtn.disabled = true;
            submitBtn.style.whiteSpace = 'nowrap';
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Tracking...</span>';

            // Build query params
            const params = new URLSearchParams({
                action: 'get_customer_tracking',
                order_id: orderId,
                email: email
            });

            // Use injected API URL (set by PHP in track_order.php to handle subdirectory deployments)
            const apiBase = window.TRACKING_API_URL || '/tracking_module_src/TrackingAPI.php';

            fetch(apiBase + '?' + params.toString(), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-search me-2"></i>Track Order';

                    if (data.status === 'success') {
                        renderTrackingUI(data.data);
                    } else {
                        showTrackingError(data.message || 'Error tracking your order. Please check the ID and Email.');
                    }
                })
                .catch(error => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-search me-2"></i>Track Order';
                    showTrackingError('A network error occurred connecting to the tracking service.');
                    console.error('Tracking Error:', error);
                });
        });
    }

    function renderTrackingUI(data) {
        const info = data.order_info;
        const shipping = data.shipping;
        const timeline = data.timeline;

        // Progress Bar Calculation
        const stageIndex = info.progress_stage_index;
        const steps = ['Pending', 'Processing', 'Shipped', 'Out for Delivery', 'Delivered'];

        let progressHtml = '<div class="tracking-progress-container mb-4"><ul class="tracking-progress">';

        if (stageIndex === -1) {
            progressHtml += '<li class="text-danger w-100 text-center"><i class="fas fa-times-circle fa-2x mb-2"></i><br>Order Cancelled</li>';
        } else {
            for (let i = 0; i < steps.length; i++) {
                let statusClass = '';
                if (i < stageIndex) statusClass = 'completed';
                if (i === stageIndex) statusClass = 'active';

                // Note: Index 2 combines Shipped & Partially Shipped conceptually here
                let label = steps[i];
                if (i === 2 && info.status === 'partially_shipped') label = 'Partially Shipped';

                progressHtml += `<li class="${statusClass}">${label}</li>`;
            }
        }
        progressHtml += '</ul></div>';

        // Tracking Meta details
        let detailsHtml = `
            <div class="row mb-4">
                <div class="col-md-6 border-end">
                    <p class="text-muted mb-1">Order ID</p>
                    <h5 class="fw-bold">#${info.order_id}</h5>
                    <p class="text-muted mb-1 mt-3">Current Status</p>
                    <span class="badge bg-primary fs-6">${info.status_formatted}</span>
                </div>
                <div class="col-md-6 ps-md-4">
                    <p class="text-muted mb-1">Courier Partner</p>
                    <h6 class="fw-bold">${shipping.courier_name || 'Processing...'}</h6>
                    
                    <p class="text-muted mb-1 mt-3">Tracking Number</p>
                    <h6 class="fw-bold">
                        ${shipping.tracking_number || 'Awaiting dispatch'} 
                        ${shipping.tracking_url ? `<a href="${shipping.tracking_url}" target="_blank" class="ms-2 fs-6"><i class="fas fa-external-link-alt"></i></a>` : ''}
                    </h6>
                    
                    <p class="text-muted mb-1 mt-3">Estimated Delivery</p>
                    <h6 class="fw-bold text-success">${shipping.estimated_delivery || 'TBD'}</h6>
                </div>
            </div>
        `;

        // Timeline History Log
        let historyHtml = '<h5 class="fw-bold mb-3 border-bottom pb-2">Tracking History</h5><div class="tracking-timeline">';
        if (timeline && timeline.length > 0) {
            timeline.forEach(log => {
                const dateObj = new Date(log.created_at);
                const dateStr = dateObj.toLocaleDateString() + ' ' + dateObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                historyHtml += `
                <div class="timeline-item pb-3 mb-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong class="text-primary">${log.status.toUpperCase().replace('_', ' ')}</strong>
                        <small class="text-muted"><i class="far fa-clock me-1"></i>${dateStr}</small>
                    </div>
                    ${log.notes ? `<p class="mb-0 text-muted small">${log.notes}</p>` : ''}
                </div>`;
            });
        } else {
            historyHtml += '<p class="text-muted">No timeline updates available yet.</p>';
        }
        historyHtml += '</div>';

        trackingResultContainer.innerHTML = `
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    ${progressHtml}
                    ${detailsHtml}
                    ${historyHtml}
                </div>
            </div>
        `;
        trackingResultContainer.classList.remove('d-none');
    }

    function showTrackingError(msg) {
        trackingResultContainer.innerHTML = `
            <div class="alert alert-danger py-3">
                <i class="fas fa-exclamation-triangle me-2"></i> ${msg}
            </div>
        `;
        trackingResultContainer.classList.remove('d-none');
    }
});
