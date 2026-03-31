var ascending = true; // Biến theo dõi trạng thái sắp xếp

function sortTable(colIndex) {
    var table, rows, switching, i, x, y, shouldSwitch;
    table = document.getElementById("myTable"); // Đã sửa thành getElementById
    switching = true;

    // Đảo ngược trạng thái sắp xếp sau mỗi lần nhấn vào tiêu đề cột
    ascending = !ascending;

    while (switching) {
        switching = false;
        rows = table.rows;
        for (i = 1; i < rows.length - 1; i++) {
            shouldSwitch = false;
            x = rows[i].getElementsByTagName("td")[colIndex];
            y = rows[i + 1].getElementsByTagName("td")[colIndex];

            // Kiểm tra trạng thái sắp xếp và thực hiện so sánh tương ứng
            if (ascending) {
                if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                    shouldSwitch = true;
                    break;
                }
            } else {
                if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                    shouldSwitch = true;
                    break;
                }
            }
        }
        if (shouldSwitch) {
            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
            switching = true;
        }
    }
}