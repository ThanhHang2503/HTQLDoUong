-- Add more products to each category (minimum 3 products per category)
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Get the latest item_id to start numbering from
-- Current max is around 116, so we'll start from 117

-- Category 1: Trà Sữa (add 1 more)
INSERT INTO items (item_name, category_id, description, unit_price, added_date, item_status) VALUES
('Trà Sữa Tàu Hủ Trứng Muối',1,'Trà sữa tàu hủ nóng với trứng muối mặn mà.',48000.00,NOW(),'active');

-- Category 2: Trà (add 1 more)
INSERT INTO items (item_name, category_id, description, unit_price, added_date, item_status) VALUES
('Trà Atisô Mật Ong',2,'Trà atisô ấm nóng tốt cho gan, hương mật ong nhẹ nhàng.',42000.00,NOW(),'active');

-- Category 3: Cà phê (add 1 more)
INSERT INTO items (item_name, category_id, description, unit_price, added_date, item_status) VALUES
('Cà Phê Trứng',3,'Cà phê trứng nóng truyền thống Hà Nội, thơm ngon bổ dưỡng.',45000.00,NOW(),'active');

-- Category 4: Nước ngọt (add 1 more)
INSERT INTO items (item_name, category_id, description, unit_price, added_date, item_status) VALUES
('Nước Ngọt Sting',4,'Nước tăng lực Sting lạnh, giải khát tức thì.',18000.00,NOW(),'active');

-- Category 5: Sinh tố (add 1 more)
INSERT INTO items (item_name, category_id, description, unit_price, added_date, item_status) VALUES
('Sinh Tố Xoài Tươi',5,'Sinh tố xoài tươi ngọt, giàu vitamin C.',43000.00,NOW(),'active');

-- Category 6: Nước ép (add 2 more)
INSERT INTO items (item_name, category_id, description, unit_price, added_date, item_status) VALUES
('Nước Ép Cà Rốt',6,'Nước ép cà rốt tươi sạch, giàu chất dinh dưỡng.',32000.00,NOW(),'active'),
('Nước Ép Kiwi',6,'Nước ép kiwi ngọt chua tự nhiên, tốt cho hệ tiêu hóa.',35000.00,NOW(),'active');

-- Category 7: Sữa chua (add 3 products)
INSERT INTO items (item_name, category_id, description, unit_price, added_date, item_status) VALUES
('Sữa Chua Dâu',7,'Sữa chua dâu tây chua ngọt vừa phải, mịn mịn.',25000.00,NOW(),'active'),
('Sữa Chua Nha Đam',7,'Sữa chua nha đam trắng, tốt cho da và tiêu hóa.',23000.00,NOW(),'active'),
('Sữa Chua Miel',7,'Sữa chua mật ong Miel nhập khẩu, thơm ngon cao cấp.',28000.00,NOW(),'active');

-- Category 8: Rượu (add 3 products)
INSERT INTO items (item_name, category_id, description, unit_price, added_date, item_status) VALUES
('Rượu Vang Đỏ Pháp',8,'Rượu vang đỏ Pháp chọn lọc, hương thơm vừa phải.',65000.00,NOW(),'active'),
('Rượu Trắng Tây Nguyên',8,'Rượu trắng cao cấp từ Tây Nguyên Việt Nam.',55000.00,NOW(),'active'),
('Rượu Mơ Nhật Bản',8,'Rượu mơ Nhật Bản hương thơm ngọt dịu, phù hợp làm quà.',48000.00,NOW(),'active');

-- Category 9: Chè (add 3 products)
INSERT INTO items (item_name, category_id, description, unit_price, added_date, item_status) VALUES
('Chè Thiệu Hùng',9,'Chè Thiệu Hùng Hà Giang nguyên chất, hương lạ.',45000.00,NOW(),'active'),
('Chè Cổ Thụ',9,'Chè cổ thụ rừng, vị đắng nhẹ sảng khoái.',52000.00,NOW(),'active'),
('Chè Lạc Xanh',9,'Chè lạc xanh tươi, hương thảo mộc tự nhiên.',38000.00,NOW(),'active');

-- Category 10: Nước đặc biệt (add 3 products)
INSERT INTO items (item_name, category_id, description, unit_price, added_date, item_status) VALUES
('Nước Đươu Gà',10,'Nước đươu gà nóng, tốt cho sức khỏe và sắc đẹp.',35000.00,NOW(),'active'),
('Nước Yến Sào',10,'Nước yến sào chính hãng nhập khẩu, bồi bổ cơ thể.',68000.00,NOW(),'active'),
('Nước Lẩu Mắm Ớt',10,'Nước lẩu mắm ớt đặc biệt, gia vị sẵn cho lẩu.',15000.00,NOW(),'active');

-- Category 11: Đồ uống truyền thống (add 3 products)
INSERT INTO items (item_name, category_id, description, unit_price, added_date, item_status) VALUES
('Cà Phê Sạch Buôn Mê Thuột',11,'Cà phê sạch nguyên chất từ Buôn Mê Thuột, vị đậm đà.',42000.00,NOW(),'active'),
('Nước Nhan Sâm Đặc',11,'Nước nhan sâm đặc nguyên chất, bồi bổ sức khỏe.',38000.00,NOW(),'active'),
('Mạch Nha Lúa Mạch',11,'Nước mạch nha lúa mạch ấm nóng, thơm lạ.',28000.00,NOW(),'active');

-- Verify the update
SELECT c.category_id, c.category_name, COUNT(i.item_id) as product_count 
FROM category c 
LEFT JOIN items i ON c.category_id = i.category_id 
GROUP BY c.category_id, c.category_name 
ORDER BY c.category_id;
