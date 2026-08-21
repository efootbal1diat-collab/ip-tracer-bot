import {
    makeWASocket,
    DisconnectReason,
    useMultiFileAuthState,
    fetchLatestBaileysVersion,
    Browsers
} from '@whiskeysockets/baileys';
import qrcode from 'qrcode-terminal';
import pino from 'pino';
import { execFile } from 'child_process';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const projectRoot = path.resolve(__dirname, '..');
const authDir = path.join(__dirname, 'wa_auth');

// Create auth directory if not exists
if (!fs.existsSync(authDir)) {
    fs.mkdirSync(authDir, { recursive: true });
}

// Keepalive timer so Node.js process stays active waiting for events
setInterval(() => {}, 1000 * 60 * 60);

console.log('====================================================');
console.log('🤖  WhatsApp AI Network Copilot (IP Tracer)');
console.log('====================================================');

// Read .env configuration
function getEnvConfig() {
    const envPath = path.join(projectRoot, '.env');
    const config = { allowedNumbers: [] };

    if (fs.existsSync(envPath)) {
        const content = fs.readFileSync(envPath, 'utf8');
        const match = content.match(/WA_ALLOWED_NUMBERS=(.*)/);
        if (match && match[1]) {
            config.allowedNumbers = match[1]
                .split(',')
                .map((n) => n.trim().replace(/[^0-9]/g, ''))
                .filter(Boolean);
        }
    }
    return config;
}

// Execute Laravel AI agent via artisan command
function askAiAgent(question) {
    return new Promise((resolve) => {
        const phpPath = 'php';
        const artisanPath = path.join(projectRoot, 'artisan');

        execFile(
            phpPath,
            [artisanPath, 'ai:ask', question],
            { cwd: projectRoot, timeout: 90000, maxBuffer: 2 * 1024 * 1024 },
            (error, stdout, stderr) => {
                if (error) {
                    console.error('❌ Error executing ai:ask:', error.message);
                    const msg = stdout || stderr || error.message;
                    resolve(`⚠️ *Gagal memproses:* ${msg.trim()}`);
                    return;
                }
                resolve(stdout.trim());
            }
        );
    });
}

let isConnecting = false;

async function startBot() {
    if (isConnecting) return;
    isConnecting = true;

    try {
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
            keepAliveIntervalMs: 25000,
        });

        // Save credentials
        sock.ev.on('creds.update', saveCreds);

        // Connection status updates
        sock.ev.on('connection.update', (update) => {
            const { connection, lastDisconnect, qr } = update;

            if (qr) {
                console.log('\n====================================================');
                console.log('📲 SCAN QR CODE INI MENGGUNAKAN WHATSAPP DI HP ANDA:');
                console.log('(Buka WA di HP > Perangkat Tertaut > Tautkan Perangkat)');
                console.log('====================================================\n');
                qrcode.generate(qr, { small: true });
                console.log('\nMenunggu scan...');
            }

            if (connection === 'close') {
                isConnecting = false;
                const statusCode = lastDisconnect?.error?.output?.statusCode;
                const shouldReconnect = statusCode !== DisconnectReason.loggedOut;

                console.log('⚠️ Sesi terputus (Status:', statusCode, ').');

                if (shouldReconnect) {
                    console.log('🔄 Menghubungkan ulang dalam 5 detik...');
                    setTimeout(startBot, 5000);
                } else {
                    console.log('❌ Perangkat telah logout. Menghapus auth dan restart...');
                    try {
                        fs.rmSync(authDir, { recursive: true, force: true });
                    } catch (e) {}
                    setTimeout(startBot, 3000);
                }
            } else if (connection === 'open') {
                isConnecting = false;
                console.log('\n====================================================');
                console.log('✅ BERHASIL TERHUBUNG KE WHATSAPP!');
                console.log('🤖 AI Network Copilot siap menerima perintah chat.');
                console.log('====================================================\n');
            }
        });

        // Handle incoming messages
        sock.ev.on('messages.upsert', async ({ messages, type }) => {
            if (type !== 'notify') return;

            for (const m of messages) {
                // Ignore self messages or status broadcasts
                if (m.key.fromMe || m.key.remoteJid === 'status@broadcast') continue;

                const remoteJid = m.key.remoteJid;
                const senderNumber = remoteJid.replace(/[^0-9]/g, '');

                // Extract text from various message types
                const text =
                    m.message?.conversation ||
                    m.message?.extendedTextMessage?.text ||
                    m.message?.imageMessage?.caption ||
                    '';

                const trimmedText = text.trim();
                if (!trimmedText) continue;

                // Whitelist check
                const { allowedNumbers } = getEnvConfig();
                if (allowedNumbers.length > 0 && !allowedNumbers.includes(senderNumber)) {
                    console.log(`⛔ Pesan diabaikan (Nomor [${senderNumber}] tidak ada di whitelist).`);
                    continue;
                }

                console.log(`📩 [${senderNumber}] bertanya: "${trimmedText}"`);

                try {
                    // Send typing indicator to WhatsApp
                    await sock.sendPresenceUpdate('composing', remoteJid);

                    // Query AI agent
                    const aiResponse = await askAiAgent(trimmedText);

                    // Send response back to chat
                    await sock.sendMessage(
                        remoteJid,
                        { text: aiResponse },
                        { quoted: m }
                    );

                    await sock.sendPresenceUpdate('paused', remoteJid);
                    console.log(`📤 Respon berhasil dikirim ke [${senderNumber}]`);
                } catch (err) {
                    console.error(`❌ Gagal membalas [${senderNumber}]:`, err.message);
                }
            }
        });
    } catch (err) {
        isConnecting = false;
        console.error('❌ Error saat memulai bot:', err.message);
        console.log('🔄 Mencoba ulang dalam 5 detik...');
        setTimeout(startBot, 5000);
    }
}

// Clean up temporary test files
const testFiles = ['test_baileys.js', 'test_keepalive.js'];
for (const tf of testFiles) {
    const p = path.join(__dirname, tf);
    if (fs.existsSync(p)) {
        try { fs.unlinkSync(p); } catch (e) {}
    }
}

// Start
startBot();
