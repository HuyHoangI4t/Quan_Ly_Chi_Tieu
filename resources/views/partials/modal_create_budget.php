<div class="modal fade" id="createBudgetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="budgetForm" novalidate>
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <h5 class="modal-title fw-bold" id="budgetModalTitle">Thiết lập ngân sách</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 pt-3 pb-4">
                    <input type="hidden" id="budget_id" name="budget_id">
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">DANH MỤC</label>
                        <div class="input-group input-group-lg cursor-pointer" onclick="(function(){var e=document.getElementById('openCategoryChooser'); if(e) e.click();})()">
                            <input type="text" id="budget_category_picker" class="form-control bg-light border-0 rounded-3 ps-3" placeholder="Chọn danh mục..." readonly style="cursor: pointer;">
                            <input type="hidden" id="budget_category" name="category_id">
                            <span class="input-group-text bg-light border-0 text-muted rounded-3 ms-1"><i class="fas fa-chevron-right"></i></span>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-7">
                            <label class="form-label fw-bold small text-muted">SỐ TIỀN</label>
                            <div class="input-group input-group-lg">
                                <input type="text" id="budget_amount_display" class="form-control fw-bold border-0 bg-light rounded-start-3" placeholder="0" oninput="formatInputMoney(this)">
                                <input type="hidden" id="budget_amount" name="amount">
                                <span class="input-group-text border-0 bg-light rounded-end-3 text-muted">₫</span>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold small text-muted">CHU KỲ</label>
                            <select id="budget_period" name="period" class="form-select form-select-lg border-0 bg-light rounded-3">
                                <option value="monthly">Tháng này</option>
                                <option value="weekly">Tuần này</option>
                                <option value="yearly">Năm này</option>
                            </select>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded-3">
                        <label class="form-label fw-bold small text-muted d-flex justify-content-between mb-2">
                            <span>CẢNH BÁO KHI ĐẠT</span>
                            <span id="thresholdValue" class="badge bg-warning text-dark">80%</span>
                        </label>
                        <input type="range" class="form-range" id="budget_threshold" name="alert_threshold" min="50" max="100" step="5" value="80" oninput="document.getElementById('thresholdValue').innerText = this.value + '%'">
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Lưu Ngân Sách</button>
                </div>
            </form>
        </div>
    </div>
</div>
