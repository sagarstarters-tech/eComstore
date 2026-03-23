<?php
session_start();
include '../includes/header.php'; // Include existing store header
?>
<style>
/* Custom Progress Bar CSS for Tracking */
.tracking-progress-container {
    width: 100%;
}
.tracking-progress {
    display: flex;
    justify-content: space-between;
    list-style-type: none;
    padding: 0;
    margin: 0;
    position: relative;
    z-index: 1;
}
.tracking-progress::before {
    content: '';
    position: absolute;
    top: 15px;
    left: 0;
    width: 100%;
    height: 4px;
    background-color: #e9ecef;
    z-index: -1;
}
.tracking-progress li {
    text-align: center;
    position: relative;
    flex: 1;
    font-size: 13px;
    font-weight: 600;
    color: #6c757d;
    padding-top: 35px;
}
.tracking-progress li::before {
    content: '';
    position: absolute;
    top: 5px;
    left: 50%;
    transform: translateX(-50%);
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background-color: #fff;
    border: 4px solid #dee2e6;
    z-index: 2;
    transition: all 0.3s ease;
}
.tracking-progress li.completed {
    color: #0d6efd;
}
.tracking-progress li.completed::before {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
.tracking-progress li.active {
    color: #0d6efd;
}
.tracking-progress li.active::before {
    background-color: #fff;
    border-color: #0d6efd;
    box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.2);
}
.tracking-progress li.completed + li::after,
.tracking-progress li.active + li::after {
    /* If we want a solid color bar fill behind, we'd style a separate element, 
       but for simplicity the circles work well. */
}
</style>

<div class="container my-5 py-4" style="min-height: 60vh;">
    
    <div class="row justify-content-center mb-5">
        <div class="col-md-8 col-lg-6 text-center">
            <h2 class="fw-bold mb-3"><i class="fas fa-search-location text-primary me-2"></i>Track Your Order</h2>
            <p class="text-muted">Enter your Order ID and Account Email below to see live shipping updates securely.</p>
        </div>
    </div>

    <div class="row justify-content-center">
        <!-- Input Form -->
        <div class="col-md-10 col-lg-8">
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-body p-4 p-md-5">
                    <form id="trackingForm" class="row gx-3 gy-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Order Number (#)</label>
                            <input type="number" id="track_order_id" class="form-control form-control-lg bg-light" placeholder="e.g. 1024" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Registered Email ID</label>
                            <input type="email" id="track_email" class="form-control form-control-lg bg-light" placeholder="user@example.com" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm"><i class="fas fa-search me-2"></i>Track</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tracking Output Container -->
            <div id="trackingResultContainer" class="d-none mt-4">
                <!-- Result populated by ajax_tracking.js -->
            </div>
        </div>
    </div>

</div>

<!-- Include JS Script -->
<script src="/tracking_module_src/examples/ajax_tracking.js"></script>

<?php include '../includes/footer.php'; ?>
