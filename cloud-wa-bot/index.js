import {
    makeWASocket,
    DisconnectReason,
    useMultiFileAuthState,
    fetchLatestBaileysVersion,
    Browsers
} from '@whiskeysockets/baileys';
import qrcode from 'qrcode-terminal';
import pino from 'pino';
import axios from 'axios';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';
import dotenv from 'dotenv';

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const authDir = path.join(__dirname, 'wa_auth');

// Keepalive timer
setInterval(() => {}, 1000 * 60 * 60);

if (!fs.existsSync(authDir)) {
    fs.mkdirSync(authDir, { recursive: true });
}

console.log('====================================================');
console.log('🤖  Cloud WhatsApp AI Network Bot (GitHub Data)');
console.log('====================================================');

const GITHUB_REPO = process.env.GITHUB_REPO || 'efootball1diat-collab/ip-tracer-bot';
const GITHUB_TOKEN = process.env.GITHUB_TOKEN || '';
const AI_API_BASE_URL = (process.env.AI_API_BASE_URL || 'https://api.openai.com/v1').replace(/\/+$/, '');
const AI_API_KEY = process.env.AI_API_KEY || '';
const AI_MODEL = process.env.AI_MODEL || 'gpt-4o-mini';
const ALLOWED_NUMBERS = (process.env.WA_ALLOWED_NUMBERS || '')
    .split(',')
    .map((n) => n.trim().replace(/[^0-9]/g, ''))
    .filter(Boolean);

// Fetch IP data from GitHub or local fallback
async function getLatestIpData() {
    // 1. Try remote GitHub raw content
    const rawUrl = `https://raw.githubusercontent.com/${GITHUB_REPO}/main/ip_data.json`;
    try {
        const headers = {};
        if (GITHUB_TOKEN) {
            headers.Authorization = `token ${GITHUB_TOKEN}`;
        }
        const res = await axios.get(rawUrl, { headers, timeout: 10000 });
        if (res.data && res.data.ip_records) {
            return res.data;
        }
    } catch (e) {
        // Fallback to local file if available
    }

    const localPath = path.join(__dirname, 'ip_data.json');
    if (fs.existsSync(localPath)) {
        try {
            return JSON.parse(fs.readFileSync(localPath, 'utf8'));
        } catch (e) {}
    }

    return null;
}

// Ask AI using GitHub dataset context
async function askAiWithData(userQuestion, ipData) {
    if (!AI_API_KEY) {
        return '⚠️ AI API Key belum dikonfigurasi di file .env (AI_API_KEY).';
    }

    const summaryStr = ipData
        ? `Subnet: ${ipData.subnet}.0/24 | Terakhir Update: ${ipData.last_updated_human || ipData.last_updated} | Total Online: ${ipData.summary.online_count} | IP Kosong: ${ipData.summary.free_available_ips} | Anomali: ${ipData.summary.active_unmapped_anomalies}`
        : 'Data IP belum tersedia.';

    // Create a compact string representation of all IP records for LLM context
    let ipRecordsStr = '';
    if (ipData && ipData.ip_records) {
        ipRecordsStr = ipData.ip_records
            .map((r) => {
                const status = r.is_online ? 'ONLINE' : 'OFFLINE';
                const user = r.excel_user ? `User:${r.excel_user}` : 'User:Kosong';
                const machine = r.excel_machine ? `Machine:${r.excel_machine}` : '';
                const vendor = r.vendor ? `Vendor:${r.vendor}` : '';
                const free = r.is_free_ip ? '[BEBAS/KOSONG]' : '';
                return `${r.full_ip} | ${status} | ${user} | ${machine} | ${vendor} | ${free}`.replace(/\s+\|\s+$/g, '');
            })
            .join('\n');
    }

    const systemPrompt = `Anda adalah AI Network Copilot untuk jaringan perusahaan.
Berikut adalah data snapshot jaringan IP kantor terbaru yang diambil dari database inventaris Excel dan scanner jaringan:

=== RINGKASAN JARINGAN ===
${summaryStr}

=== DAFTAR DATA IP (1-254) ===
${ipRecordsStr}

Tugas Anda:
1. Jawab pertanyaan user mengenai status IP, kepemilikan user, mesin, port, atau alokasi IP kosong dengan akurat berdasarkan data di atas.
2. Jika user meminta IP kosong, pilihkan dari IP yang bertanda [BEBAS/KOSONG] (status offline dan belum terdaftar).
3. Format jawaban menggunakan WhatsApp Markdown (*tebal*, bullet points •, ringkas dan mudah dibaca di layar HP).`;

    try {
        const response = await axios.post(
            `${AI_API_BASE_URL}/chat/completions`,
            {
                model: AI_MODEL,
                messages: [
                    { role: 'system', content: systemPrompt },
                    { role: 'user', content: userQuestion }
                ],
                stream: false
            },
            {
                headers: {
                    Authorization: `Bearer ${AI_API_KEY}`,
                    'Content-Type': 'application/json'
                },
                timeout: 60000
            }
        );

        return response.data?.choices?.[0]?.message?.content || 'Analisis selesai tanpa pesan tambahan.';
    } catch (err) {
        const errMsg = err.response?.data?.error?.message || err.message;
        return `⚠️ Terjadi kendala saat memproses dengan AI: ${errMsg}`;
    }
}

async function startBot() {
    const { state, saveCreds } = await useMultiFileAuthState(authDir);
    const { version } = await fetchLatestBaileysVersion().catch(() => ({
        version: [2, 3000, 1043857760]
    }));

    console.log(`📡 Inisialisasi WhatsApp Socket (v${version.join('.')})...`);

    const sock = makeWASocket({
        version,
        logger: pino({ level: 'silent' }),
        printQRInTerminal: false,
        auth: state,
        browser: Browsers.windows('Desktop'),
        connectTimeoutMs: 60000,
        defaultQueryTimeoutMs: 60000,
        keepAliveIntervalMs: 25000
    });

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            console.log('\n====================================================');
            console.log('📲 SCAN QR CODE INI MENGGUNAKAN WHATSAPP DI HP ANDA:');
            console.log('====================================================\n');
            qrcode.generate(qr, { small: true });
        }

        if (connection === 'close') {
            const statusCode = lastDisconnect?.error?.output?.statusCode;
            const shouldReconnect = statusCode !== DisconnectReason.loggedOut;
            console.log('⚠️ Sesi terputus (Status:', statusCode, ').');

            if (shouldReconnect) {
                console.log('🔄 Menghubungkan ulang dalam 5 detik...');
                setTimeout(startBot, 5000);
            } else {
                console.log('❌ Logout. Menghapus sesi dan restart...');
                try {
                    fs.rmSync(authDir, { recursive: true, force: true });
                } catch (e) {}
                setTimeout(startBot, 3000);
            }
        } else if (connection === 'open') {
            console.log('\n====================================================');
            console.log('✅ CLOUD WHATSAPP BOT BERHASIL ONLINE!');
            console.log('🤖 Siap melayani tanya jawab IP dari HP via internet luar.');
            console.log('====================================================\n');
        }
    });

    sock.ev.on('messages.upsert', async ({ messages, type }) => {
        if (type !== 'notify') return;

        for (const m of messages) {
            if (m.key.fromMe || m.key.remoteJid === 'status@broadcast') continue;

            const remoteJid = m.key.remoteJid;
            const senderNumber = remoteJid.replace(/[^0-9]/g, '');

            const text =
                m.message?.conversation ||
                m.message?.extendedTextMessage?.text ||
                m.message?.imageMessage?.caption ||
                '';

            const trimmedText = text.trim();
            if (!trimmedText) continue;

            if (ALLOWED_NUMBERS.length > 0 && !ALLOWED_NUMBERS.includes(senderNumber)) {
                console.log(`⛔ Diabaikan dari [${senderNumber}] (Bukan nomor terdaftar)`);
                continue;
            }

            console.log(`📩 [${senderNumber}] bertanya: "${trimmedText}"`);

            try {
                await sock.sendPresenceUpdate('composing', remoteJid);

                // Fetch latest data snapshot from GitHub
                const ipData = await getLatestIpData();

                // Process with AI
                const reply = await askAiWithData(trimmedText, ipData);

                await sock.sendPresenceUpdate('paused', remoteJid);

                await sock.sendMessage(remoteJid, { text: reply }, { quoted: m });
                console.log(`📤 Respon terkirim ke [${senderNumber}]`);
            } catch (err) {
                console.error(`❌ Gagal membalas [${senderNumber}]:`, err.message);
            }
        }
    });
}

startBot();
