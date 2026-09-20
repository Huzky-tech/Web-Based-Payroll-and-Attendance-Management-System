<div class="position-settings-section">
    <div class="position-toolbar">
        <div>
            <div class="section-title">Employee Positions &amp; Salaries</div>
            <div class="section-sub">Positions used by employee forms and their suggested rates.</div>
        </div>
        <button class="btn-action" type="button" id="openPositionCatalogModal"><i class="fas fa-plus"></i>Add Position</button>
    </div>
    <div class="table-container">
        <table>
            <thead><tr><th>Position</th><th>Hourly Rate</th><th>Monthly Salary</th><th>Action</th></tr></thead>
            <tbody id="positionCatalogTableBody"><tr><td colspan="4">Loading positions...</td></tr></tbody>
        </table>
    </div>
</div>

<div class="position-modal-overlay" id="positionCatalogModal" aria-hidden="true">
    <div class="position-modal" role="dialog" aria-modal="true" aria-labelledby="positionCatalogModalTitle">
        <div class="position-modal-header">
            <div><h2 id="positionCatalogModalTitle">Add New Position</h2><p>Enter the position and its suggested salary rates.</p></div>
            <button type="button" class="position-modal-close" id="closePositionCatalogModal" aria-label="Close modal"><i class="fas fa-times"></i></button>
        </div>
        <form id="positionCatalogForm" novalidate>
            <input type="hidden" id="catalog_position_id" value="">
            <div class="position-form-field">
                <label for="catalog_position_name">Position Name</label>
                <input type="text" id="catalog_position_name" maxlength="100" placeholder="Enter position name" autocomplete="off" required>
                <div class="position-field-error" id="catalog_position_name_error" aria-live="polite"></div>
            </div>
            <div class="position-rate-grid">
                <div class="position-form-field">
                    <label for="catalog_hourly_rate">Hourly Rate (PHP)</label>
                    <input type="number" id="catalog_hourly_rate" min="0.01" step="0.01" placeholder="0.00" required>
                    <div class="position-field-error" id="catalog_hourly_rate_error" aria-live="polite"></div>
                </div>
                <div class="position-form-field">
                    <label for="catalog_salary_rate">Monthly Salary (PHP)</label>
                    <input type="number" id="catalog_salary_rate" min="0.01" step="0.01" placeholder="0.00" required>
                    <div class="position-field-error" id="catalog_salary_rate_error" aria-live="polite"></div>
                </div>
            </div>
            <div class="position-modal-actions">
                <button class="btn-secondary" type="button" id="positionCatalogCancel">Cancel</button>
                <button class="btn-action" type="submit" id="positionCatalogSubmit"><i class="fas fa-plus"></i>Add Position</button>
            </div>
        </form>
    </div>
</div>
