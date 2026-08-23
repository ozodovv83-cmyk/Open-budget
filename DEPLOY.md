# Railway'ga joylashtirish yo'riqnomasi

## 1. Loyihani Railway'ga yuklash

1. Bu papkadagi fayllarni (`index.php`, `composer.json`, `Procfile`, `.gitignore`) GitHub repositoryga yuklang (yoki Railway CLI orqali to'g'ridan-to'g'ri deploy qiling).
2. Railway'da **New Project → Deploy from GitHub repo** ni tanlang va shu repo'ni ulang.
3. Railway PHP'ni avtomatik aniqlaydi (`composer.json` fayli borligi sababli) va `Procfile`dagi buyruq bilan ishga tushiradi.

## 2. Environment Variables (Railway → Settings → Variables)

Quyidagilarni albatta qo'shing:

| Nomi | Qiymati | Izoh |
|------|---------|------|
| `BOT_TOKEN` | `123456789:AAxxxxxxxxxxxxxxxxxxxxxxxxxxxxx` | @BotFather'dan olingan haqiqiy token |
| `ADMIN_ID` | `123456789` | Sizning Telegram ID raqamingiz (masalan @userinfobot orqali oling) |
| `DATA_DIR` | `/data` | Pastdagi Volume bilan mos bo'lishi shart |

## 3. Persistent Volume ulash (JUDA MUHIM!)

Bu qadamni o'tkazib yubormang — aks holda har safar Railway loyihangizni qayta ishga tushirganda (deploy, restart, crash) **barcha foydalanuvchi balanslari, adminlar ro'yxati va sozlamalar butunlay o'chib ketadi.**

1. Railway loyihangizda **Settings → Volumes** bo'limiga o'ting
2. **+ New Volume** tugmasini bosing
3. **Mount path**: `/data` deb yozing (yuqoridagi `DATA_DIR` bilan bir xil bo'lishi kerak)
4. Saqlang — Railway avtomatik qayta deploy qiladi

## 4. Telegram webhook o'rnatish

Bot Railway'da ishga tushgandan so'ng, unga Railway domenini beradi (masalan `https://sizning-loyiha.up.railway.app`). Quyidagi manzilni brauzerda oching (BOT_TOKEN va domenni o'zingiznikiga almashtiring):

```
https://api.telegram.org/bot<BOT_TOKEN>/setWebhook?url=https://sizning-loyiha.up.railway.app/index.php
```

Muvaffaqiyatli bo'lsa, `{"ok":true,"result":true,"description":"Webhook was set"}` javobini ko'rasiz.

Webhook holatini tekshirish uchun:
```
https://api.telegram.org/bot<BOT_TOKEN>/getWebhookInfo
```

## 5. Botni sinab ko'rish

Telegram'da botingizga `/start` yuboring — asosiy menyu chiqishi kerak.

## Muhim eslatmalar

- **Fayl-asosli saqlash**: Bot barcha ma'lumotlarni (balans, adminlar, sozlamalar) oddiy `.txt` fayllarda saqlaydi — bu ko'p sonli foydalanuvchi (minglab) uchun ideal emas, lekin kichik/o'rta loyihalar uchun ishlaydi. Kelajakda MySQL/PostgreSQL'ga o'tish tavsiya etiladi.
- **`php -S` server**: Bu PHP'ning oddiy o'rnatilgan serveri bo'lib, so'rovlarni birma-bir (ketma-ket) qayta ishlaydi. Kichik-o'rta trafik uchun yetarli, lekin juda yuqori trafikda (bir vaqtning o'zida minglab foydalanuvchi) sekinlashishi mumkin. Agar shunday bo'lsa, Nginx + PHP-FPM konfiguratsiyasiga o'tish kerak bo'ladi.
- Agar `BOT_TOKEN` sozlanmagan bo'lsa, bot HTTP 500 xatosi bilan javob beradi (bu ataylab qilingan xavfsizlik chegarasi).
