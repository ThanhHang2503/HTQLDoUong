# AUDIT REPORT – HỆ THỐNG THÔNG TIN ELDERCOFFEE
**Dự án:** HTQLDoUong | **DB:** `eldercoffee_db` | **Stack:** PHP thuần + MySQL + Bootstrap 5
**Ngày audit:** 2026-04-10 | **Auditor:** Antigravity (System Analysis Agent)

---

# PHẦN A. TỔNG QUAN HỆ THỐNG HIỆN TẠI

## A1. Công nghệ sử dụng

| Mục | Chi tiết |
|-----|---------|
| **PHP** | PHP thuần (không framework). Tuy nhiên, phần warehouse/HR dùng namespace và class OOP (Models/Repositories/Services pattern) |
| **Database** | MySQL (`eldercoffee_db`), kết nối bằng `mysqli_connect()` trong `config.php`, charset `utf8mb4` |
| **Frontend** | Bootstrap 5.3.3 (CDN), FontAwesome 6.5.1, jQuery minimal (bootstrapDatepicker), jspdf (CDN) |
| **Session/Auth** | Có `session_start()`, dùng `$_SESSION['account_id']`, `$_SESSION['role_name']` |
| **Phân quyền** | Có file `authorization.php` với class `AppRole`, `AppPermission`, hàm `can()`, `requireRole()`, `requirePermission()` |
| **Upload file** | **Không có** – không tìm thấy xử lý upload ảnh sản phẩm |
| **Export PDF** | Có nhúng jspdf (CDN) nhưng **chưa implement** |
| **Export Excel/CSV** | Có hàm `exportCsv()` trong `WarehouseReportService` nhưng **chưa có giao diện** |
| **MVC** | Không theo đúng MVC. Warehouse và HR có lớp phân tách (Models/Repositories/Services) nhưng view và controller bị trộn trong `user_page.php` |
| **Composer** | Có `composer.json` nhưng chỉ dùng `psr/log` |
| **REST API** | Có `api/hr/index.php` và `api/warehouse/index.php` là JSON API, nhưng giao diện HTML chưa gọi vào |

## A2. Cấu trúc thư mục và file

```
HTQLDoUong/
├── config.php                 → Kết nối DB (barebone: 4 dòng)
├── index.php                  → Redirect sang login_form.php
├── login_form.php             → Đăng nhập (email + md5 pass)
├── register.php               → Đăng ký tài khoản (có chọn role!)
├── logout.php                 → Hủy session + redirect login
├── user_page.php              → Controller trung tâm (566 dòng - quá fat)
├── procedure_and_fuction.txt  → Logic thống kê (đặt tên .txt nhưng là PHP - LỖI)
│
├── admin/                     → Trang Admin riêng biệt
│   ├── index.php              → Quản lý sản phẩm + nhà cung cấp (tìm/lọc/sắp xếp)
│   ├── includes/bootstrap.php → Auth check ADMIN, helper functions, query functions
│   └── components/            → SearchBox.php, FilterPanel.php, Table.php
│
├── src/
│   ├── models/                → Logic cũ kiểu procedural
│   │   ├── authorization.php  → AppRole, AppPermission, can(), requireRole()
│   │   ├── functions.php      → Helper functions (có lỗi: hardcode password DB)
│   │   ├── them_*/sua_*/xoa_* → CRUD handlers (procedural, inline SQL)
│   │   ├── thongke.php        → Logic thống kê hóa đơn
│   │   └── in_donhang.php     → In hóa đơn
│   ├── views/                 → Giao diện HTML (PHP + HTML mixed)
│   │   ├── header.php         → Sidebar điều hướng (phân vai trò)
│   │   ├── dashboard.php      → Tổng quan số liệu
│   │   ├── sanpham.php        → Danh sách sản phẩm
│   │   ├── donhang.php        → Danh sách hóa đơn
│   │   ├── khachhang.php      → Danh sách khách hàng
│   │   ├── nhansu.php         → Danh sách nhân sự (accounts)
│   │   ├── phieunhap.php      → Form nhập kho (CRUD đầy đủ)
│   │   ├── kho_thongke.php    → Thống kê tồn kho (lọc, cảnh báo tồn thấp)
│   │   ├── nhacungcap.php     → CRUD nhà cung cấp
│   │   ├── loai.php           → CRUD danh mục
│   │   └── thongke.php        → Thống kê hóa đơn theo ngày
│   ├── warehouse/             → Module kho OOP
│   │   ├── Models.php         → Item, Supplier, InventoryReceipt, StockMovement
│   │   ├── Repositories.php   → Data access layer cho warehouse
│   │   └── Services.php       → GoodsReceiptService, GoodsExportService, WarehouseReportService
│   └── hr/                    → Module HR OOP
│       ├── Models.php         → Position, PositionHistory, SalaryRecord, LeaveRequest, ResignationRequest
│       ├── Repositories.php   → Data access layer cho HR
│       └── Services.php       → EmployeeService, SalaryService, LeaveService, ResignationService
│
├── api/
│   ├── hr/index.php           → REST API endpoints cho HR (20+ routes)
│   └── warehouse/index.php    → REST API endpoints cho warehouse
│
├── database/                  → SQL migration files (KHÔNG có file dump đầy đủ!)
│   ├── migrate_warehouse_exports_v3.sql
│   ├── migrate_stock_non_zero_v4.sql
│   └── warehouse_stock_quick_test_v1.sql
│
└── css/, javascript/, img/    → Static assets
```

## A3. Luồng hoạt động hiện tại

```
index.php → login_form.php (hoặc register.php)
    ↓ (sau đăng nhập)
    ├── role = admin → admin/index.php (xem SP + NCC với tìm/lọc)
    └── role ≠ admin → user_page.php?home (dashboard)
                            ↓ (routing qua GET params)
                            ?sanpham    → CRUD sản phẩm
                            ?donhang    → CRUD hóa đơn/bán hàng
                            ?khachhang  → CRUD khách hàng
                            ?nhansu     → CRUD accounts (nhân sự)
                            ?loai       → CRUD danh mục
                            ?nhacungcap → CRUD nhà cung cấp
                            ?phieunhap  → Phiếu nhập kho (đầy đủ nhất)
                            ?kho_thongke→ Thống kê tồn kho
                            ?thongke    → Thống kê hóa đơn theo ngày
```

**Vấn đề thiết kế:** `user_page.php` vừa là controller (if/switch routing), vừa chứa business logic inline (INSERT/UPDATE SQL trực tiếp), vừa render view bằng include. **God file, khó mở rộng.**

---

# PHẦN B. KHẢO SÁT CÁC CHỨC NĂNG ĐÃ CÓ

## B1. Tài khoản và xác thực

| STT | Chức năng | Trạng thái | Mức độ hoàn thiện | File/Module | Ghi chú |
|-----|-----------|------------|-------------------|-------------|---------|
| 1 | Đăng nhập | ✅ Đã có | Hoạt động tốt | `login_form.php` | Dùng `md5()` – **KHÔNG AN TOÀN** (nên dùng `password_hash`) |
| 2 | Đăng xuất | ✅ Đã có | Đủ | `logout.php` | OK |
| 3 | Đăng ký | ✅ Đã có | Có nhưng nguy hiểm | `register.php` | **Cho phép chọn role admin!** – Ai cũng register được admin |
| 4 | Quên mật khẩu | ❌ Chưa có | — | — | Không có |
| 5 | Đổi mật khẩu | ❌ Chưa có | — | — | Không có |
| 6 | Quản lý session | ⚠️ Một phần | Cơ bản | `user_page.php` | Có check session nhưng không có timeout |
| 7 | Phân quyền theo role | ✅ Đã có | Hoạt động tốt | `authorization.php` | 4 roles: admin/manager/sales/warehouse |

> **⚠️ LỖ HỔNG BẢO MẬT NGHIÊM TRỌNG:** `register.php` cho phép chọn `role_name=admin` tự do. Không nên để publicopen register với role cấp cao.

## B2. Quản lý người dùng

| STT | Chức năng | Trạng thái | Mức độ hoàn thiện | File/Module | Ghi chú |
|-----|-----------|------------|-------------------|-------------|---------|
| 1 | Xem danh sách user | ✅ Đã có | Hoạt động | `views/nhansu.php` | Hiển thị tên, email, role, status |
| 2 | Thêm user | ✅ Đã có | Hoạt động | `models/them_nhansu.php` | |
| 3 | Sửa user | ✅ Đã có | Hoạt động | `models/sua_nhansu.php` | Sửa được tên, email, role, password |
| 4 | Xóa user | ✅ Đã có | Soft delete | `models/xoa_nhansu.php` | Xóa = set status='inactive', có bảo vệ tự xóa mình |
| 5 | Khóa/mở tài khoản | ✅ Đã có | Hoạt động | `views/nhansu.php` + `xoa_nhansu.php` | Có khôi phục tài khoản |
| 6 | Gán quyền | ✅ Đã có | Qua role_id | `models/sua_nhansu.php` | Phân quyền qua role, không phải permission riêng lẻ |
| 7 | Hồ sơ cá nhân | ❌ Chưa có | — | — | Nhân viên không xem/sửa được thông tin bản thân |

## B3. Quản lý sản phẩm / đồ uống

| STT | Chức năng | Trạng thái | Mức độ hoàn thiện | File/Module | Ghi chú |
|-----|-----------|------------|-------------------|-------------|---------|
| 1 | Danh sách sản phẩm | ✅ Đã có | Hoạt động | `views/sanpham.php` | |
| 2 | Thêm sản phẩm | ✅ Đã có | Hoạt động | `models/them_sanpham.php` | Thiếu upload ảnh |
| 3 | Sửa sản phẩm | ✅ Đã có | Hoạt động | `models/sua_sanpham.php` | |
| 4 | Xóa sản phẩm | ✅ Đã có | Soft delete | `models/xoa_sanpham.php` | |
| 5 | Loại/danh mục | ✅ Đã có | Hoạt động | `views/loai.php` + models | |
| 6 | Giá bán | ✅ Đã có | Trong form sửa | `models/sua_sanpham.php` | |
| 7 | Giá nhập | ✅ Đã có | Cập nhật qua phiếu nhập | `src/warehouse/Services.php` | |
| 8 | Số lượng tồn | ✅ Đã có | Tự động cập nhật | `src/warehouse/Services.php` | |
| 9 | Ảnh sản phẩm | ❌ Chưa có | — | — | Không có upload/quản lý ảnh |
| 10 | Tìm kiếm SP | ⚠️ Một phần | Cơ bản | `models/timkiem_sanpham.php` | Chỉ tìm theo tên, không lọc nâng cao |
| 11 | Lọc/sắp xếp SP | ✅ Đã có (admin) | Đầy đủ hơn | `admin/index.php` | Admin có filter nâng cao (giá, status, ngày, danh mục) |

## B4. Quản lý kho

| STT | Chức năng | Trạng thái | Mức độ hoàn thiện | File/Module | Ghi chú |
|-----|-----------|------------|-------------------|-------------|---------|
| 1 | Theo dõi tồn kho | ✅ Đã có | Đầy đủ | `views/kho_thongke.php` | Có lọc, cảnh báo tồn thấp, giá trị tồn |
| 2 | Nhập kho | ✅ Đã có | **Tốt nhất trong hệ thống** | `views/phieunhap.php` + `Services.php` | Transaction ACID, stock movement log |
| 3 | Xuất kho | ⚠️ Một phần | Backend có, frontend thiếu | `warehouse/Services.php` (GoodsExportService) | Có service, có API, nhưng `?phieuxuat` → redirect sang phieunhap! |
| 4 | Phiếu nhập | ✅ Đã có | Đầy đủ (CRUD) | `views/phieunhap.php` | Tạo/sửa/xóa phiếu, lịch sử 20 phiếu gần nhất |
| 5 | Lịch sử nhập kho | ✅ Đã có | Hiển thị 20 gần nhất | `views/phieunhap.php` | Limit 20, không có phân trang |
| 6 | Cảnh báo hết hàng | ✅ Đã có | Hoạt động | `views/kho_thongke.php` | Ngưỡng tùy chỉnh được |
| 7 | Nhà cung cấp | ✅ Đã có | Đầy đủ | `views/nhacungcap.php` | CRUD đầy đủ |

## B5. Quản lý bán hàng / kinh doanh

| STT | Chức năng | Trạng thái | Mức độ hoàn thiện | File/Module | Ghi chú |
|-----|-----------|------------|-------------------|-------------|---------|
| 1 | Giỏ hàng | ✅ Đã có | Giao diện tạo đơn | `models/them_donhang.php` (16KB) | Chọn SP + qty trong form |
| 2 | Đặt hàng | ✅ Đã có | Lưu vào DB | `models/luu_donhang.php` | |
| 3 | Tạo hóa đơn | ✅ Đã có | Hoạt động | `models/them_donhang.php` + `functions.php` | |
| 4 | In hóa đơn | ✅ Đã có | Có giao diện in | `models/in_donhang.php` | Giao diện đơn giản, có nút print |
| 5 | Danh sách đơn hàng | ✅ Đã có | Hoạt động | `views/donhang.php` | Hiển thị list, tìm theo mã |
| 6 | Sửa đơn hàng | ❌ Chưa có | Code để chú thích | `user_page.php` L275-278 | `sua_donhang.php` là file rỗng |
| 7 | Trạng thái đơn | ❌ Chưa có | — | — | Không có cột status cho invoice |
| 8 | Doanh thu theo thời gian | ✅ Đã có | Cơ bản | `views/thongke.php` + `procedure_and_fuction.txt` | Tìm theo khoảng ngày |
| 9 | Lợi nhuận | ❌ Chưa có | — | — | Không tính (doanh thu - giá nhập) |
| 10 | Thống kê top KH, top SP | ✅ Đã có | Hoạt động | `thongke.php` | KH mua nhiều nhất, SP bán chạy, NV tích cực |

## B6. Quản trị hệ thống

| STT | Chức năng | Trạng thái | Mức độ hoàn thiện | File/Module | Ghi chú |
|-----|-----------|------------|-------------------|-------------|---------|
| 1 | Dashboard | ✅ Đã có | Cơ bản | `views/dashboard.php` | Hiển thị số tổng: SP, nhân viên, KH, doanh thu |
| 2 | Báo cáo theo thời gian | ⚠️ Một phần | Chỉ hóa đơn | `views/thongke.php` | Chưa có BC kho, lương, NCC theo kỳ |
| 3 | Tìm kiếm nâng cao | ⚠️ Một phần | Chỉ admin trang | `admin/index.php` | User_page chỉ có tìm cơ bản |
| 4 | Backup/restore | ❌ Chưa có | — | — | Không có giao diện/chức năng |
| 5 | Cấu hình hệ thống | ❌ Chưa có | — | — | |
| 6 | Nhật ký hoạt động | ⚠️ Một phần | Chỉ stock movement | `stock_movements` table | Không có audit log chung |

## B7. Quản lý nhân sự

| STT | Chức năng | Trạng thái | Mức độ hoàn thiện | File/Module | Ghi chú |
|-----|-----------|------------|-------------------|-------------|---------|
| 1 | Danh sách nhân viên | ✅ Đã có | Hoạt động | `views/nhansu.php` | |
| 2 | Thêm/xóa/sửa nhân sự | ✅ Đã có | Hoạt động | `models/them_nhansu.php` | |
| 3 | Chức vụ (position) | ✅ Có backend | Backend sẵn sàng | `src/hr/Models.php`, `Services.php` | Có class Position, PositionHistory, nhưng **không có giao diện** |
| 4 | Lịch sử chức vụ | ✅ Có backend | Backend sẵn sàng | `src/hr/Services.php::changePosition()` | **Không có giao diện** |
| 5 | Tính lương | ✅ Có backend | Backend sẵn sàng | `src/hr/Services.php::SalaryService` | SalaryRecord model, calculateSalary(), **không có giao diện** |
| 6 | Xem bảng lương | ❌ Không có UI | — | — | API có `/api/hr/salary/records` nhưng không có view PHP |
| 7 | In bảng lương | ❌ Chưa có | — | — | |
| 8 | Đơn nghỉ phép | ✅ Có backend | Backend sẵn sàng | `src/hr/Services.php::LeaveService` | LeaveRequest model, **không có giao diện** |
| 9 | Đơn nghỉ việc | ✅ Có backend | Backend sẵn sàng | `src/hr/Services.php::ResignationService` | **Không có giao diện** |
| 10 | Duyệt đơn | ✅ Có backend | Backend sẵn sàng | `api/hr/index.php` | API route approve/reject, **không có giao diện** |
| 11 | Thống kê nhân sự | ✅ Có backend | Backend sẵn sàng | `src/hr/Services.php::HRStatisticsService` | **Không có giao diện** |

> **Nhận xét HR:** Module HR được đầu tư thiết kế backend rất tốt (OOP, repository pattern, REST API đầy đủ), nhưng **hoàn toàn thiếu giao diện HTML**. Người dùng không thể dùng được từ trình duyệt.

## B8. Giao diện và trải nghiệm người dùng

| Mục | Trạng thái | Ghi chú |
|-----|-----------|---------|
| Giao diện riêng cho Admin | ✅ Có | `admin/index.php` – sidebar riêng, filter đẹp hơn |
| Giao diện riêng cho nhân viên | ✅ Có | `user_page.php` với sidebar phân vai trò |
| Giao diện NV Kho | ✅ Có | Sidebar hiển thị mục kho khi role=warehouse |
| Giao diện NV Bán hàng | ✅ Có | Sidebar hiển thị đơn hàng khi role=sales |
| Responsive | ⚠️ Một phần | Bootstrap 5 nhưng sidebar cố định width, mobile khó dùng |
| Form validate | ⚠️ Một phần | Có `required` HTML5, một số PHP validate nhưng **không nhất quán** |
| Thông báo lỗi/thành công | ✅ Có | Dùng session flash message, alert Bootstrap |

---

# PHẦN C. KHẢO SÁT DATABASE HIỆN TẠI

## C1. Danh sách bảng (suy luận từ code + migration files)

> **Lưu ý:** Không có file `dump.sql` đầy đủ trong project. Danh sách bảng được suy luận từ các câu SQL trong code.

| Bảng | Chức năng | Nguồn xác nhận |
|------|-----------|---------------|
| `accounts` | Tài khoản người dùng (id, full_name, email, password, role_id, position_id, status) | login_form.php, nhansu.php |
| `roles` | Bảng vai trò (id, name) – admin/manager/sales/warehouse | authorization.php |
| `items` | Sản phẩm (item_id, item_name, category_id, description, unit_price, purchase_price, stock_quantity, item_status, added_date) | sanpham.php, kho_thongke.php |
| `category` | Danh mục sản phẩm (category_id, category_name) | loai.php |
| `customers` | Khách hàng (customer_id, customer_name, phone_number) | khachhang.php, functions.php |
| `invoices` | Hóa đơn (invoice_id, account_id, customer_id, discount, total, creation_time) | donhang.php, thongke.php |
| `invoice_details` | Chi tiết hóa đơn (invoice_id, item_id, quantity) | functions.php::taoHoaDon() |
| `suppliers` | Nhà cung cấp (supplier_id, supplier_code, supplier_name, contact_name, phone_number, email, address, status, created_at) | nhacungcap.php, admin/bootstrap.php |
| `inventory_receipts` | Phiếu nhập kho (receipt_id, receipt_code, supplier_id, import_date, total_value, note, status, created_by) | phieunhap.php, Services.php |
| `inventory_receipt_items` | Chi tiết phiếu nhập (receipt_item_id, receipt_id, item_id, quantity, import_price, line_total) | Services.php |
| `stock_movements` | Lịch sử biến động kho (movement_id, item_id, movement_type, quantity_change, stock_before, stock_after, unit_cost, reference_type, reference_id, note, created_by) | Services.php |
| `inventory_exports` | Phiếu xuất kho (export_id, export_code, export_date, total_value, note, created_by) | migrate_warehouse_exports_v3.sql |
| `inventory_export_items` | Chi tiết phiếu xuất (export_item_id, export_id, item_id, quantity, unit_price, line_total) | migrate_warehouse_exports_v3.sql |
| `positions` | Chức vụ nhân sự (position_id, position_name, base_salary, description, is_active) | hr/Models.php, Services.php |
| `employee_positions_history` | Lịch sử chức vụ (history_id, account_id, position_id, start_date, end_date, reason) | hr/Services.php |
| `salary_records` | Bản ghi lương (salary_record_id, account_id, position_id, salary_month, salary_year, base_salary, allowance, bonus, deductions, total_salary, notes) | hr/Models.php |
| `leave_requests` | Đơn nghỉ phép (leave_request_id, account_id, from_date, to_date, leave_type, reason, status, approved_by, approved_at) | hr/Models.php |
| `resignation_requests` | Đơn nghỉ việc (resignation_request_id, account_id, notice_date, effective_date, reason, status, approved_by) | hr/Models.php |

**Tổng:** ~18 bảng (suy luận). **Chưa xác nhận được từ dump SQL thực tế.**

## C2. Đánh giá thiết kế CSDL

| Tiêu chí | Đánh giá |
|----------|---------|
| Khóa chính | Có AUTO_INCREMENT PK trên các bảng chính |
| Khóa ngoại | Có FK trong migration files (inventory_exports, v.v.), nhưng không chắc các bảng cũ có FK không |
| Quan hệ | accounts→roles (N-1), items→category (N-1), invoices→customers/accounts (N-1), inventory_receipts→suppliers (N-1), v.v. |
| Dư thừa dữ liệu | `accounts.password` không hash đúng chuẩn (md5); `accounts.position_id` vừa denormalize từ positions_history |
| Thiếu bảng | Không có bảng cấu hình hệ thống, không có audit/activity log chung |
| Không có file dump | **Nghiêm trọng:** Chỉ có 3 file migration nhỏ, không có SQL đầy đủ để setup từ đầu |

## C3. So khớp database với nghiệp vụ

| Nghiệp vụ | Bảng hỗ trợ | Trạng thái |
|-----------|------------|-----------|
| Quản lý user | `accounts`, `roles` | ✅ Đủ cơ bản |
| Quản lý sản phẩm | `items`, `category` | ✅ Đủ (thiếu cột ảnh) |
| Quản lý kho | `inventory_receipts`, `inventory_receipt_items`, `inventory_exports`, `inventory_export_items`, `stock_movements` | ✅ Tốt nhất hệ thống |
| Quản lý bán hàng | `invoices`, `invoice_details`, `customers` | ⚠️ Thiếu: trạng thái đơn, phương thức thanh toán |
| Quản lý nhân sự | `accounts`, `positions`, `employee_positions_history` | ✅ Cơ bản đủ |
| Bảng lương | `salary_records` | ✅ Thiết kế tốt (có allowance, bonus, deductions) |
| Đơn nghỉ phép | `leave_requests` | ✅ Thiết kế tốt |
| Đơn nghỉ việc | `resignation_requests` | ✅ Thiết kế tốt |
| Nhà cung cấp | `suppliers` | ✅ Đủ |
| Phiếu nhập | `inventory_receipts`, `inventory_receipt_items` | ✅ Tốt |
| Phiếu xuất (độc lập) | `inventory_exports`, `inventory_export_items` | ✅ Có, nhưng giao diện thiếu |
| Báo cáo thống kê | Dựa trên các bảng trên | ⚠️ Cần thêm VIEW hoặc stored procedure |
| Backup/restore | — | ❌ Không có |

**Cần bổ sung bảng:**
- `system_config` – cấu hình hệ thống
- `activity_logs` – nhật ký hoạt động người dùng
- `invoice_status_history` – lịch sử trạng thái đơn hàng (tùy chọn)

---

# PHẦN D. ĐỐI CHIẾU VỚI YÊU CẦU ĐỒ ÁN

| Yêu cầu đồ án | Hiện trạng web | Đáp ứng? | Cần bổ sung |
|--------------|----------------|----------|-------------|
| Giới thiệu doanh nghiệp | Không có trang mô tả | ❌ | Thêm trang About hoặc hồ sơ công ty |
| Khảo sát HTTT | Chưa có tài liệu | ❌ | Viết tài liệu khảo sát |
| Phân tích bài toán | Chưa có tài liệu | ❌ | Viết tài liệu phân tích |
| Sơ đồ chức năng | Chưa có | ❌ | Vẽ BFD/MFD |
| Sơ đồ ngữ cảnh | Chưa có | ❌ | Vẽ Context Diagram |
| DFD mức đỉnh | Chưa có | ❌ | Vẽ DFD Level 0 |
| DFD mức dưới đỉnh | Chưa có | ❌ | Vẽ DFD Level 1 |
| Thiết kế CSDL | Có nhưng không đầy đủ | ⚠️ | Cần ERD + data dictionary + dump SQL đầy đủ |
| Thiết kế giao diện | Có giao diện hoạt động | ✅ | Chụp màn hình, thêm mockup |
| Cài đặt và bảo trì | Chưa có hướng dẫn | ❌ | Viết README cài đặt XAMPP |
| Hướng dẫn sử dụng | Không có | ❌ | Viết user manual |
| Backup/restore CSDL | Không có | ❌ | Thêm chức năng hoặc hướng dẫn phpMyAdmin |
| **Admin** | Có giao diện admin riêng | ✅ | Bổ sung thêm chức năng |
| **Quản lý nhân sự** | Backend OK, **thiếu UI hoàn toàn** | ⚠️ 30% | Xây dựng giao diện cho: lương, đơn, chức vụ |
| **Quản lý kho** | Phiếu nhập OK, phiếu xuất thiếu UI | ⚠️ 70% | Thêm giao diện phiếu xuất |
| **Quản lý kinh doanh** | Có bán hàng cơ bản | ⚠️ 60% | Thêm trạng thái đơn, lợi nhuận |
| **Báo cáo thống kê** | Chỉ thống kê hóa đơn | ⚠️ 40% | BC lương, BC kho theo kỳ |
| **Phân quyền** | Có 4 vai trò, có enforce | ✅ | Đủ cơ bản |
| Dữ liệu demo | Không có seeder | ❌ | Cần SQL seed data |
| Tài khoản test | Không có | ❌ | Tạo tài khoản mẫu mỗi role |

---

# PHẦN E. KẾT LUẬN HIỆN TRẠNG

## E1. Mức độ hệ thống hiện tại

> **Kết luận:** Hệ thống đang ở mức **"website quản lý bán hàng + kho có cơ sở nâng cấp thành HTTT doanh nghiệp"** – chưa đủ chuẩn đồ án, nhưng có nền tảng backend tốt hơn vẻ ngoài.

## E2. Những phần mạnh nhất hiện có

1. **Module kho (warehouse):** Thiết kế OOP tốt nhất, có transaction ACID, stock movement log, phiếu nhập CRUD đầy đủ
2. **Phân quyền (authorization):** Clean, centralized, dùng constants, có `can()`, `requirePermission()`
3. **Backend HR:** Đã có Models, Services, REST API cho lương, đơn nghỉ, chức vụ – chỉ thiếu UI
4. **Admin panel:** Filter/search nâng cao cho sản phẩm và nhà cung cấp

## E3. Những lỗ hổng lớn nhất

1. **Bảo mật:** `md5()` password, register tự chọn role admin, một số SQL dùng `mysqli_real_escape_string` nhưng không nhất quán
2. **HR module không có UI:** Backend đầu tư tốt nhưng người dùng không tiếp cận được
3. **Phiếu xuất không có UI:** `?phieuxuat` redirect về phieunhap
4. **Không có file database đầy đủ:** Thiếu dump SQL → không thể restore/setup từ đầu
5. **`user_page.php` là God File:** 566 dòng, mix controller + business logic + view
6. **`functions.php` có hardcode password DB khác** (dòng 24) – lỗi nghiêm trọng
7. **`procedure_and_fuction.txt`** là code PHP nhưng đặt tên `.txt` – không được include đúng cách
8. **Không có dữ liệu demo, tài khoản test**

## E4. Ưu tiên cần làm trước

1. Giao diện HR (lương, đơn nghỉ, chức vụ) → từ backend đã có
2. Giao diện phiếu xuất kho
3. Sửa lỗ hổng bảo mật register
4. Tạo dump database đầy đủ
5. Tạo seed data + tài khoản test

## E5. Có nên tái cấu trúc không?

**Không cần tái cấu trúc toàn bộ.** Nền tảng đủ tốt để tiếp tục. Chỉ cần:
- Tách logic ra khỏi `user_page.php` dần dần
- Build giao diện cho các module backend đã sẵn sàng

---

# PHẦN F. LỘ TRÌNH NÂNG CẤP

## Giai đoạn 1: Vá lỗi và chuẩn hóa (1-2 ngày)

- [ ] **Bảo mật:** Sửa `register.php` – không cho chọn role admin (chỉ admin mới tạo admin)
- [ ] **Bảo mật:** Xóa hardcode password trong `functions.php` dòng 24
- [ ] **DB:** Export dump SQL đầy đủ từ phpMyAdmin → lưu vào `database/eldercoffee_db.sql`
- [ ] **File:** Đổi tên `procedure_and_fuction.txt` → `src/models/thongke_logic.php` và include đúng cách
- [ ] **DB:** Đảm bảo tất cả bảng HR (`positions`, `salary_records`, `leave_requests`, `resignation_requests`, `employee_positions_history`) đã được tạo
- [ ] **Phiếu xuất:** Gỡ redirect, thay bằng route thực

## Giai đoạn 2: Bổ sung chức năng cốt lõi (3-5 ngày)

### Ưu tiên cao (từ backend đã sẵn sàng):

- [ ] **UI Phiếu xuất kho** (`?phieuxuat`) – tương tự phieunhap.php, dùng `GoodsExportService`
- [ ] **UI Chức vụ nhân sự** – danh sách positions, thêm/sửa/xóa
- [ ] **UI Đổi chức vụ nhân viên** + xem lịch sử chức vụ
- [ ] **UI Bảng lương** – form nhập lương tháng, xem danh sách lương NV
- [ ] **UI Đơn nghỉ phép** – NV tạo đơn, Manager/Admin duyệt
- [ ] **UI Đơn nghỉ việc** – tương tự
- [ ] **Hồ sơ cá nhân** – NV xem/sửa thông tin + đổi mật khẩu

### Ưu tiên trung bình:

- [ ] **Báo cáo kho theo kỳ** – xuất nhập trong tháng/quý
- [ ] **Báo cáo lương theo tháng/năm** – tổng quỹ lương
- [ ] **Báo cáo lợi nhuận** = doanh thu - chi phí nhập
- [ ] **Trạng thái đơn hàng** – thêm cột `status` vào invoices
- [ ] **Dashboard cải thiện** – thêm chart doanh thu tháng (Chart.js)
- [ ] **Backup/restore** – giao diện simple hoặc hướng dẫn phpMyAdmin export

### Bảo mật:

- [ ] Thay `md5()` bằng `password_hash()` / `password_verify()`
- [ ] Thêm CSRF token cho các form POST

## Giai đoạn 3: Hoàn thiện báo cáo và demo (1-2 ngày)

- [ ] **Seed data SQL:** Tạo ít nhất 10 SP, 5 KH, 20 hóa đơn, 3-5 phiếu nhập, 3 nhân viên mỗi role
- [ ] **Tài khoản test:**
  - admin@eldercoffee.com / admin123
  - manager@eldercoffee.com / manager123
  - sales@eldercoffee.com / sales123
  - warehouse@eldercoffee.com / warehouse123
- [ ] **README.md:** Hướng dẫn cài XAMPP, import DB, truy cập hệ thống
- [ ] **Chụp màn hình** tất cả màn hình chính → đưa vào báo cáo
- [ ] **Vẽ ERD** từ cấu trúc DB hiện có (có thể dùng phpMyAdmin Designer)
- [ ] **Vẽ DFD** và sơ đồ chức năng (có thể dùng draw.io)
- [ ] **Hướng dẫn sử dụng:** PDF mô tả từng chức năng có hình ảnh

---

# TÓM TẮT AUDIT (1 TRANG)

## ✅ ĐÃ CÓ
- Đăng nhập/đăng xuất, phân quyền 4 vai trò
- CRUD sản phẩm, danh mục, khách hàng, nhà cung cấp
- Tạo/in hóa đơn bán hàng, tìm kiếm đơn
- **Phiếu nhập kho đầy đủ** (CRUD, transaction, stock movement log)
- **Thống kê tồn kho** với lọc, cảnh báo hết hàng
- Dashboard số liệu tổng quan
- Thống kê hóa đơn theo khoảng ngày
- **Backend HR hoàn chỉnh:** positions, salary, leave, resignation, API REST
- Admin panel với tìm kiếm/lọc nâng cao
- Quản lý nhân sự cơ bản (accounts)

## ❌ THIẾU (so với yêu cầu đồ án)
- **Giao diện HR:** lương, đơn nghỉ, chức vụ (backend có, UI không)
- **Giao diện phiếu xuất kho**
- Hồ sơ cá nhân, đổi mật khẩu
- Báo cáo lợi nhuận, báo cáo lương
- Lỗ hổng bảo mật: md5, register tự chọn admin role
- Không có file dump SQL đầy đủ để backup/restore
- Không có dữ liệu demo, không có tài khoản test
- Không có tài liệu (ERD, DFD, hướng dẫn sử dụng, README)

## 🔧 CẦN LÀM NGAY (theo thứ tự)
1. Xuất dump SQL đầy đủ
2. Sửa bảo mật register + functions.php
3. Xây dựng giao diện HR (chức vụ → lương → đơn nghỉ)
4. Thêm giao diện phiếu xuất kho
5. Tạo seed data + tài khoản test
6. Viết README và hướng dẫn sử dụng
7. Vẽ ERD, sơ đồ chức năng cho báo cáo
