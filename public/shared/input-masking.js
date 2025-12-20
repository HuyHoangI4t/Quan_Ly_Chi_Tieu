/*
 * Tiện ích định dạng input (Input Masking)
 * Định dạng ô nhập số với phân cách hàng nghìn
 */

class InputMasking {
    constructor() {
        this.init();
    }

    /**
     * Khởi tạo tất cả input tiền tệ với định dạng
     */
    init() {
        // Find all amount inputs
        const amountInputs = document.querySelectorAll('input[type="number"][name*="amount"], input.amount-input');
        amountInputs.forEach(input => {
            this.applyMask(input);
        });
    }

    /**
     * Áp dụng định dạng cho một input cụ thể
     */
    applyMask(input) {
        // Đổi type thành text để cho phép định dạng
        input.setAttribute('type', 'text');
        input.setAttribute('inputmode', 'numeric');
        input.setAttribute('pattern', '[0-9,]*');

        // Lưu giá trị số thực
        let actualValue = input.value ? parseFloat(input.value) || 0 : 0;
        
        // Định dạng giá trị ban đầu nếu có
        if (actualValue) {
            input.value = this.formatNumber(actualValue);
        }

        // Thêm placeholder nếu chưa có
        if (!input.placeholder) {
            input.placeholder = '0';
        }

        // Xử lý sự kiện input
        input.addEventListener('input', (e) => {
            const cursorPosition = e.target.selectionStart;
            const oldLength = e.target.value.length;

            // Loại bỏ mọi ký tự không phải số
            let value = e.target.value.replace(/[^\d]/g, '');
            
            // Chuyển về số nguyên
            actualValue = value ? parseInt(value, 10) : 0;
            
            // Định dạng với phân cách hàng nghìn
            const formattedValue = this.formatNumber(actualValue);
            e.target.value = formattedValue;

            // Khôi phục vị trí con trỏ
            const newLength = formattedValue.length;
            const lengthDiff = newLength - oldLength;
            const newPosition = cursorPosition + lengthDiff;
            
            e.target.setSelectionRange(newPosition, newPosition);

            // Lưu giá trị thực trong thuộc tính data
            e.target.dataset.numericValue = actualValue.toString();
        });

        // Xử lý sự kiện focus - chọn toàn bộ
        input.addEventListener('focus', (e) => {
            setTimeout(() => {
                e.target.select();
            }, 0);
        });

        // Xử lý sự kiện blur - đảm bảo định dạng hợp lệ
        input.addEventListener('blur', (e) => {
            const value = parseInt(e.target.value.replace(/[^\d]/g, ''), 10) || 0;
            e.target.value = this.formatNumber(value);
            e.target.dataset.numericValue = value.toString();
        });

        // Xử lý submit form - chuyển giá trị về dạng số
        const form = input.closest('form');
        if (form && !form.dataset.maskingHandled) {
            form.dataset.maskingHandled = 'true';
            form.addEventListener('submit', (e) => {
                // Tìm tất cả input đã được định dạng trong form này
                const maskedInputs = form.querySelectorAll('input[data-numeric-value]');
                maskedInputs.forEach(maskedInput => {
                    // Tạo input ẩn chứa giá trị số
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = maskedInput.name;
                    hiddenInput.value = maskedInput.dataset.numericValue || '0';
                    
                    // Tạm thời vô hiệu hóa input đã định dạng
                    maskedInput.disabled = true;
                    
                    // Thêm input ẩn vào form
                    form.appendChild(hiddenInput);

                    // Bật lại input đã định dạng sau khi submit
                    setTimeout(() => {
                        maskedInput.disabled = false;
                        hiddenInput.remove();
                    }, 100);
                });
            });
        }
    }

    /**
     * Định dạng số với phân cách hàng nghìn
     */
    formatNumber(num) {
        if (isNaN(num) || num === null || num === undefined) return '0';
        
        // Convert to number and ensure it's an integer
        const value = Math.floor(Number(num));
        
        // Format with thousand separator (comma)
        return value.toLocaleString('en-US').replace(/,/g, ',');
    }

    /**
     * Chuyển chuỗi đã định dạng sang số
     */
    parseNumber(str) {
        if (typeof str !== 'string') return Number(str) || 0;
        return parseInt(str.replace(/[^\d]/g, ''), 10) || 0;
    }

    /**
     * Khởi tạo lại định dạng (hữu ích khi thêm input động)
     */
    reinit() {
        this.init();
    }

    /**
     * Áp định dạng cho input mới được thêm
     */
    addMaskToInput(input) {
        if (input && input.tagName === 'INPUT') {
            this.applyMask(input);
        }
    }

    /**
     * Get numeric value from a masked input
     */
    getNumericValue(input) {
        return parseInt(input.dataset.numericValue || '0', 10);
    }
}

// Khởi tạo khi DOM sẵn sàng
let inputMaskingInstance;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        inputMaskingInstance = new InputMasking();
        window.InputMasking = inputMaskingInstance;
    });
} else {
    inputMaskingInstance = new InputMasking();
    window.InputMasking = inputMaskingInstance;
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = InputMasking;
}
