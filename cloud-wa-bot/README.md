# Cloud WhatsApp AI Bot for IP Tracer

Bot WhatsApp AI yang membaca data IP jaringan kantor dari repository GitHub Mas secara berkala dan menjawab pertanyaan seputar IP dari WhatsApp di HP Mas via internet luar.

---

## 🚀 Cara Cepat Deploy ke Cloud Gratis (Render.com)

1. **Buka [Render.com](https://render.com/)** &rarr; Daftar / Login menggunakan akun GitHub Mas (`efootball1diat-collab`).
2. Klik tombol **New +** &rarr; Pilih **Background Worker** (atau **Web Service**).
3. Sambungkan ke repository: `efootball1diat-collab/ip-tracer-bot`.
4. Isi konfigurasi:
   - **Root Directory:** `cloud-wa-bot`
   - **Build Command:** `npm install`
   - **Start Command:** `node index.js`
   - **Environment Variables:**
     - `GITHUB_REPO`: `efootball1diat-collab/ip-tracer-bot`
     - `GITHUB_TOKEN`: (isi token GitHub Mas jika repo private)
     - `AI_API_BASE_URL`: `https://generativelanguage.googleapis.com/v1beta/openai` (atau endpoint AI Mas)
     - `AI_API_KEY`: (API Key AI Mas)
     - `AI_MODEL`: `gemini-1.5-flash`
5. Klik **Create Web Service**.
6. Buka tab **Logs** di Render &rarr; Mas akan melihat **QR Code WhatsApp** muncul di log.
7. Buka WhatsApp di HP &rarr; **Perangkat Tertaut** &rarr; **Scan QR**.
8. **Selesai!** Bot WhatsApp Mas sekarang online 24/7 di cloud!

---

## 🔄 Cara Update Data IP dari Komputer Kantor

Kapan pun Mas ingin meng-update data IP terbaru ke GitHub, buka terminal di laptop kantor dan jalankan:
```bash
php artisan ip:sync-github
```
Data akan otomatis ter-update di GitHub dan bot WA akan langsung membaca data terbaru!
