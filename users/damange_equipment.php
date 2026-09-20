
    <div class="modal-overlay" id="weatherModal">
        <div class="modal-container">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-icon-wrapper" style="background: var(--blue-light); color: var(--blue-icon);">
                        <i class="fas fa-cloud-rain"></i>
                    </div>
                    <div>
                        <div class="modal-title">Weather Delay <span>Report</span></div>
                        <div class="modal-subtitle">Main Street Project &bull; 2023-07-10</div>
                    </div>
                </div>
                <button class="close-modal" onclick="closeModal('weatherModal')"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="modal-body">
                <div class="info-cards-row">
                    <div class="info-card">
                        <div class="info-label"><i class="far fa-user"></i> Reported By</div>
                        <div class="info-value">John Smith (Timekeeper)</div>
                    </div>
                    <div class="info-card">
                        <div class="info-label"><i class="far fa-clock"></i> Submitted At</div>
                        <div class="info-value">2023-07-10 14:30</div>
                    </div>
                </div>

                <div>
                    <span class="section-label">Description</span>
                    <div class="desc-box">
                        <div class="desc-text">Heavy rain started at 2PM causing work stoppage for outdoor teams.</div>
                    </div>
                </div>

                <div class="detail-box-blue">
                    <div class="box-title"><i class="fas fa-cloud-rain"></i> Weather Impact Details</div>
                    <div class="info-cards-row" style="margin-bottom: 0;">
                        <div>
                            <div class="info-label" style="color: var(--blue-icon);">Start Time</div>
                            <div class="info-value" style="color: var(--blue-icon); font-size: 16px;">14:00</div>
                        </div>
                        <div>
                            <div class="info-label" style="color: var(--blue-icon);">End Time</div>
                            <div class="info-value" style="color: var(--blue-icon); font-size: 16px;">16:00</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn-outline" onclick="closeModal('weatherModal')">Close</button>
                <button class="btn-reject"><i class="far fa-times-circle"></i> Reject</button>
                <button class="btn-approve"><i class="far fa-check-circle"></i> Approve & Process</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="damageModal">
        <div class="modal-container">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-icon-wrapper" style="background: var(--red-light); color: var(--red-icon);">
                        <i class="fas fa-wrench"></i>
                    </div>
                    <div>
                        <div class="modal-title">Equipment Damage <span>Report</span></div>
                        <div class="modal-subtitle">Downtown Office Complex &bull; 2023-07-12</div>
                    </div>
                </div>
                <button class="close-modal" onclick="closeModal('damageModal')"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="modal-body">
                <div class="info-cards-row">
                    <div class="info-card">
                        <div class="info-label"><i class="far fa-user"></i> Reported By</div>
                        <div class="info-value">Sarah Johnson (Timekeeper)</div>
                    </div>
                    <div class="info-card">
                        <div class="info-label"><i class="far fa-clock"></i> Submitted At</div>
                        <div class="info-value">2023-07-12 09:15</div>
                    </div>
                </div>

                <div>
                    <span class="section-label">Description</span>
                    <div class="desc-box">
                        <div class="desc-text">Worker dropped the power drill from the second floor scaffolding.</div>
                    </div>
                </div>

                <div class="detail-box-red">
                    <div class="box-title"><i class="fas fa-wrench"></i> Damage & Responsibility</div>
                    
                    <div class="inner-cards">
                        <div class="inner-card">
                            <div class="inner-card-label">Worker Responsible</div>
                            <div class="emp-cell" style="gap: 10px; margin-top: 4px;">
                                <div class="emp-avatar" style="background: #FEE2E2; color: #DC2626;">M</div>
                                <div class="emp-info">
                                    <span class="inner-card-value">Mike Ross</span>
                                </div>
                            </div>
                        </div>
                        <div class="inner-card">
                            <div class="inner-card-label">Equipment</div>
                            <div class="inner-card-value" style="margin-top: 8px;">DeWalt Power Drill 20V</div>
                            <div class="inner-card-sub">Est. Repair Cost: ₱ 4,500</div>
                        </div>
                    </div>

                    <div style="margin-top: 20px;">
                        <span class="box-title" style="margin-bottom: 8px;"><i class="fas fa-dollar-sign"></i> Payroll Deduction</span>
                        <div class="payroll-input-group">
                            <input type="number" value="2250">
                            <div class="suffix">PHP</div>
                        </div>
                        <div class="disclaimer-text">* This amount will be deducted from the worker's next payroll upon approval.</div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn-outline" onclick="closeModal('damageModal')">Close</button>
                <button class="btn-reject"><i class="far fa-times-circle"></i> Reject</button>
                <button class="btn-approve"><i class="far fa-check-circle"></i> Approve & Process</button>
            </div>
        </div>
    </div>
    