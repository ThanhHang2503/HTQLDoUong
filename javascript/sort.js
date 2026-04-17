/**
 * Sắp xếp bảng Sản phẩm chuẩn xác (Hỗ trợ số và tiếng Việt)
 */
function sortTable(colIndex) {
    const table = document.getElementById("myTable");
    if (!table) return;

    const tbody = table.tBodies[0];
    const rows = Array.from(tbody.rows);
    
    // 1. Xác định hướng sắp xếp (Toggle ASC/DESC)
    let dir = (table.dataset.lastSortCol == colIndex && table.dataset.sortDir === 'asc') ? 'desc' : 'asc';
    table.dataset.lastSortCol = colIndex;
    table.dataset.sortDir = dir;

    // 2. Thực hiện sắp xếp
    rows.sort((a, b) => {
        let xVal = a.cells[colIndex].textContent.trim();
        let yVal = b.cells[colIndex].textContent.trim();

        // Hàm làm sạch và chuyển đổi sang số
        const cleanNum = (str) => {
            // Loại bỏ dấu chấm phân cách nghìn, thay phẩy bằng chấm, xóa đơn vị tính
            const n = parseFloat(str.replace(/\./g, '').replace(/,/g, '.').replace(/[^\d.-]/g, ''));
            return isNaN(n) ? null : n;
        };

        const xNum = cleanNum(xVal);
        const yNum = cleanNum(yVal);

        // Nếu cả 2 đều là số, so sánh số
        if (xNum !== null && yNum !== null) {
            if (xNum !== yNum) {
                return dir === 'asc' ? xNum - yNum : yNum - xNum;
            }
        }

        // So sánh chuỗi (Tiếng Việt) nếu là chữ hoặc khi số bằng nhau
        return dir === 'asc' 
            ? xVal.localeCompare(yVal, 'vi', { sensitivity: 'base' }) 
            : yVal.localeCompare(xVal, 'vi', { sensitivity: 'base' });
    });

    // 3. Cập nhật lại giao diện (DOM)
    tbody.append(...rows);

    // 4. Cập nhật biểu tượng (Icon) hiển thị
    updateSortIcons(table, colIndex, dir);
}


/**
 * Hiển thị mũi tên lên/xuống tương ứng
 */
function updateSortIcons(table, activeIdx, dir) {
    const icons = table.querySelectorAll('thead th i');
    icons.forEach((icon, i) => {
        // Tìm parent th của icon để biết nó thuộc cột nào
        const th = icon.closest('th');
        const headerCells = Array.from(table.tHead.rows[0].cells);
        const colIdx = headerCells.indexOf(th);

        if (colIdx === activeIdx) {
            icon.className = `p-0 btn fa-solid fa-sort-${dir === 'asc' ? 'up' : 'down'} text-primary`;
        } else {
            icon.className = 'p-0 btn fa-solid fa-sort text-muted';
        }
    });
}