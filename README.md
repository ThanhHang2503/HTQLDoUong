# 🥤 Website Bán Đồ Uống

## 📌 Giới thiệu

Đây là một website bán đồ uống (nước giải khát, cà phê, trà sữa,...) được xây dựng bằng PHP và MySQL.
Hệ thống hỗ trợ người dùng xem sản phẩm, đặt hàng và quản lý đơn hàng cơ bản.


---

## ⚙️ Hướng dẫn cài đặt (chạy bằng XAMPP)

### 🔹 Bước 1: Cài đặt XAMPP

Tải và cài đặt XAMPP nếu chưa có.

---

### 🔹 Bước 2: Copy project vào htdocs

* Copy thư mục project vào:

```
C:\xampp\htdocs\
```

* Ví dụ:

```
C:\xampp\htdocs\HTQLDoUong
```

---

### 🔹 Bước 3: Khởi động server

Mở XAMPP Control Panel và bật:

* Apache
* MySQL

---

### 🔹 Bước 4: Import database

1. Truy cập:

```
http://localhost/phpmyadmin
```

2. Tạo database mới tên eldercoffee_db

3. Import file `.sql` có sẵn trong project

---

### 🔹 Bước 5: Cấu hình kết nối database

Mở file cấu hình và chỉnh lại nếu chưa phù hợp:

```php
$host = "localhost";
$user = "root";
$password = "";
$database = "eldercoffee_db";
```

---

### 🔹 Bước 6: Chạy website

Mở trình duyệt và truy cập:

```
http://localhost/HTQLDoUong
```

Giao diện admin riêng nằm tại:

```
http://localhost/HTQLDoUong/admin/
```

## ⭐ Ghi chú

Đây là project học tập, có thể còn thiếu sót. Mọi đóng góp đều được hoan nghênh!
