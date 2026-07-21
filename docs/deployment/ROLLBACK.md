# Rollback — NIDQC

> Đọc file này **trước khi** deploy, không phải lúc đang cháy.

---

## 1. Nguyên tắc

1. **Khôi phục dịch vụ trước, điều tra sau.** Không debug trên production đang hỏng.
2. **Không sửa vá trực tiếp trên production.** Rollback về trạng thái đã biết là tốt.
3. **Bình tĩnh.** Rollback vội mà không backup có thể làm hỏng nặng hơn.

## 2. Khi nào rollback

| Tình huống | Hành động |
|---|---|
| Trang trắng / lỗi 500 hàng loạt | 🔴 Rollback ngay |
| Lộ dữ liệu / lỗ hổng bảo mật | 🔴 Rollback ngay + báo quản trị |
| Mất nội dung | 🔴 Rollback ngay |
| Sai lệch giao diện nhỏ | 🟡 Sửa ở lần deploy sau |
| Một trang lỗi, phần còn lại ổn | 🟡 Cân nhắc — có thể chỉ cần tắt tính năng đó |

## 3. Quy trình

```bash
# 1. Bật bảo trì NGAY
drush state:set system.maintenance_mode 1

# 2. ⚠️ SAO LƯU TRẠNG THÁI ĐANG HỎNG TRƯỚC KHI ĐỘNG VÀO
#    Đây là bằng chứng duy nhất để điều tra sau này. Đừng bỏ qua vì vội.
drush sql:dump --gzip --result-file=/backup/BROKEN-$(date +%F-%H%M).sql.gz

# 3. Quay code về phiên bản trước
git log --oneline -5
git checkout <commit-tốt-cuối-cùng>
composer install --no-dev --optimize-autoloader

# 4. Build lại frontend Nuxt từ code CŨ + restart tiến trình SSR
#    .output/ KHÔNG nằm trong git — nếu không build lại, tiến trình SSR vẫn
#    chạy bản build MỚI dù code đã lùi.
cd frontend && npm ci && npm run build && cd ..
systemctl restart nidqc-nuxt        # hoặc: pm2 restart nidqc-nuxt

# 5. Phục hồi DB (CHỈ khi deploy đã chạy updb/cim làm đổi DB)
#    Cách A: DB/nội dung KHÔNG ở git → phục hồi từ backup phía server.
#    Config lưu trong DB nên restore DB cũng revert cấu trúc (không cần cim lại).
gunzip < /backup/nidqc-<trước-deploy>.sql.gz | drush sql:cli

# 6. Phục hồi files (nếu cần)
tar xzf /backup/files-<trước-deploy>.tar.gz -C /

# 7. Xoá cache
drush cr

# 8. Kiểm tra (cả Drupal lẫn Nuxt SSR)
curl -I https://nidqc.gov.vn/                    # trang do Nuxt render
curl -I https://nidqc.gov.vn/jsonapi/node/news   # Drupal JSON:API

# 9. Tắt bảo trì
drush state:set system.maintenance_mode 0
```

## 4. Điểm khó nhất: rollback database

> **Đây là lý do rollback thất bại.**

`drush updb` và `drush cim` **đổi cấu trúc DB**. Code quay lại được, DB thì **không tự quay lại**.

- Code cũ + DB mới = có thể hỏng nặng hơn cả bản deploy lỗi.
- Nếu deploy có chạy `updb`/`cim` → **bắt buộc** phục hồi DB từ backup, không chỉ quay code.
- Phục hồi DB = **mất toàn bộ nội dung biên tập viên nhập từ lúc backup**.

Vì vậy:
- Backup **ngay sát trước** khi deploy, không phải "backup hàng đêm".
- Deploy ngoài giờ làm việc → giảm lượng nội dung mất.
- Deploy có đổi schema → cân nhắc kỹ hơn nhiều, báo trước cho biên tập viên.

### Frontend (Nuxt SSR) cũng phải lùi

`.output/` không nằm trong git. Lùi code mà **không build lại + restart tiến trình SSR**
thì frontend vẫn phục vụ bản mới → luôn làm bước §3.4. Nuxt và Drupal là hai tiến trình
riêng: rollback phải đồng bộ cả hai, nếu không frontend cũ gọi JSON:API cấu trúc mới
(hoặc ngược lại) sẽ lệch.

### Cách A: DB & nội dung không ở git

Nội dung chỉ sống ở DB production → rollback DB **hoàn toàn** dựa vào backup phía server
(cron `drush sql:dump`, đẩy lên object storage; ở dev là `ddev snapshot`). Git không lùi
được nội dung. Vì config lưu trong DB, restore DB đưa luôn cấu trúc (node type/field/alias)
về đúng mốc — không cần `drush cim` riêng.

## 5. Không có backup thì sao

Không có backup thì **không có rollback**. Chỉ còn:
- Sửa xuôi (fix forward) — chậm, rủi ro, dưới áp lực.
- Chấp nhận mất dữ liệu.

Đây là lý do "đã sao lưu **và đã thử phục hồi**" là mục bắt buộc trong `DEPLOYMENT.md` §5.
Backup chưa từng thử phục hồi không phải là backup — nó chỉ là một file.

## 6. Sau khi rollback

- [ ] Xác nhận site chạy lại bình thường
- [ ] Báo các bên liên quan
- [ ] **Giữ bản dump BROKEN** để điều tra
- [ ] Ghi lại: cái gì hỏng, vì sao, làm sao phát hiện sớm hơn
- [ ] Viết task sửa — **không** deploy lại cho tới khi hiểu rõ nguyên nhân
- [ ] Bổ sung test để lỗi này không lặp lại

## 7. Liên hệ khẩn

> Điền trước khi deploy lần đầu. Lúc sự cố không ai đi tìm số điện thoại.

| Vai | Người | Liên hệ |
|---|---|---|
| Quản trị hệ thống | | |
| Trưởng nhóm dev | | |
| Đại diện NIDQC | | |
| Nhà cung cấp hosting | | |
