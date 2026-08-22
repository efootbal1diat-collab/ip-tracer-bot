export default async function handler(req, res) {
    // CORS headers
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

    if (req.method === 'OPTIONS') {
        return res.status(200).end();
    }

    if (req.method !== 'POST') {
        return res.status(405).json({ error: 'Method Not Allowed' });
    }

    const { message, history = [] } = req.body || {};

    if (!message || !message.trim()) {
        return res.status(400).json({ error: 'Pesan tidak boleh kosong.' });
    }

    const GITHUB_REPO = process.env.GITHUB_REPO || 'efootbal1diat-collab/ip-tracer-bot';
    const GITHUB_TOKEN = process.env.GITHUB_TOKEN || '';
    let AI_API_BASE_URL = (process.env.AI_API_BASE_URL || '').trim().replace(/\/+$/, '');
    const AI_API_KEY = (process.env.AI_API_KEY || '').trim();
    let AI_MODEL = (process.env.AI_MODEL || 'gemini-3.6-flash').trim();

    if (!AI_API_KEY) {
        return res.status(400).json({
            error: 'AI_API_KEY belum diisi di Environment Variables Vercel.'
        });
    }

    // Guard against localhost on cloud
    if (AI_API_BASE_URL.includes('localhost') || AI_API_BASE_URL.includes('127.0.0.1')) {
        return res.status(400).json({
            error: 'AI_API_BASE_URL di Vercel tidak boleh "localhost". Masukkan URL publik cloud (misal https://api.openai.com/v1 atau URL OpenCode Mas).'
        });
    }

    try {
        // 1. Fetch latest IP data snapshot from GitHub (bypassing CDN 5-minute cache)
        let ipData = null;
        try {
            const timestamp = Date.now();
            const rawUrl = `https://raw.githubusercontent.com/${GITHUB_REPO}/main/ip_data.json?_t=${timestamp}`;
            const headers = {
                'User-Agent': 'IP-Tracer-Vercel-AI',
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache'
            };
            if (GITHUB_TOKEN) {
                headers['Authorization'] = `token ${GITHUB_TOKEN}`;
            }

            const fetchRes = await fetch(rawUrl, {
                headers,
                cache: 'no-store'
            });
            if (fetchRes.ok) {
                ipData = await fetchRes.json();
            }
        } catch (e) {
            console.error('Error fetching IP data from GitHub:', e);
        }

        const summaryStr = ipData
            ? `Subnet: ${ipData.subnet}.0/24 | Terakhir Update: ${ipData.last_updated_human || ipData.last_updated} | Total Online: ${ipData.summary?.online_count || 0} | IP Kosong: ${ipData.summary?.free_available_ips || 0} | Anomali: ${ipData.summary?.active_unmapped_anomalies || 0}`
            : 'Data IP belum tersedia dari GitHub.';

        // Build compact string representation of all 254 IP records for LLM context
        let ipRecordsStr = '';
        if (ipData && Array.isArray(ipData.ip_records)) {
            ipRecordsStr = ipData.ip_records
                .map((r) => {
                    const status = r.is_online ? `ONLINE (${r.response_time_ms ? r.response_time_ms + 'ms' : 'Active'})` : 'OFFLINE';
                    const user = r.excel_user ? `User: "${r.excel_user}"` : 'User: (Kosong/Unassigned)';
                    const machine = r.excel_machine ? `Machine: "${r.excel_machine}"` : '';
                    const os = r.excel_windows ? `OS: ${r.excel_windows}` : '';
                    const mac = r.mac_address ? `MAC: ${r.mac_address}` : '';
                    const vendor = r.vendor ? `Vendor: ${r.vendor}` : '';
                    const dev = r.probable_device ? `Device: ${r.probable_device}` : '';
                    const ports = (r.open_ports && r.open_ports.length > 0) ? `Ports: [${r.open_ports.join(',')}]` : '';
                    const free = r.is_free_ip ? '[BEBAS/KOSONG_SIAP_PAKAI]' : '';
                    const anomaly = (r.is_online && (!r.excel_user || r.excel_user === 'Kosong') && !r.excel_machine) ? '[ANOMALI_AKTIF_TANPA_EXCEL]' : '';
                    
                    return `${r.full_ip} | ${status} | ${user} | ${machine} | ${os} | ${mac} | ${vendor} | ${dev} | ${ports} | ${free} | ${anomaly}`
                        .replace(/\s+\|\s+(?=\||$)/g, '')
                        .replace(/\|\s*$/g, '');
                })
                .join('\n');
        }

        const systemPrompt = `Anda adalah AI Network Copilot pribadi & asisten IT terpercaya untuk administrator jaringan perusahaan.
User yang sedang chat adalah IT Administrator Utama. Anda memiliki hak akses penuh ke seluruh data inventaris dan scanning subnet ${ipData?.subnet || '172.16.250'}.0/24.

=== RINGKASAN JARINGAN TERKINI ===
${summaryStr}

=== DATABASE LENGKAP SELURUH IP (1 - 254) ===
${ipRecordsStr}

PRINSIP KERJA: 100% TRANSPARAN, LENGKAP & TO-THE-POINT (NO GATEKEEPING)
1. TRANSPARANSI PENUH: Jawab setiap pertanyaan user dengan jujur, terbuka, dan lengkap apa adanya berdasarkan database di atas. Jangan ragu membeberkan nama user, nama mesin, IP, MAC address, port terbuka, perangkat tidak dikenal, maupun anomali jaringan.
2. LANGSUNG BERIKAN DAFTAR RINCIAN (JANGAN TANYA BALIK):
   - Jika user menanyakan "Anomali", "IP Kosong", "Daftar User", "Printer", atau "Server", JANGAN HANYA MENJAWAB JUMLAHNYA!
   - LANGSUNG sebutkan dan daftarkan seluruh IP yang dimaksud beserta detailnya (IP, Nama User/Mesin, Vendor/Device, Status).
3. DEFINISI STATUS:
   - [ANOMALI_AKTIF_TANPA_EXCEL]: Perangkat yang sedang HIDUP/ONLINE di LAN tetapi belum tercatat di data Excel inventaris kantor.
   - [BEBAS/KOSONG_SIAP_PAKAI]: IP yang OFFLINE dan BELUM ADA PEMILIKNYA di Excel (100% aman untuk diberikan ke komputer/alat baru).
4. WAKTU & STATISTIK RESMI TERKINI:
   - Waktu snapshot resmi: ${ipData?.last_updated_human || 'Terbaru'}.
   - Kondisi aktual: ${ipData?.summary?.online_count || 0} Online, ${ipData?.summary?.free_available_ips || 0} Bebas/Kosong, ${ipData?.summary?.active_unmapped_anomalies || 0} Anomali.
   - Gunakan selalu data terkini ini dan abaikan riwayat lama.
5. FORMAT TAMPILAN MOBILE:
   - Sajikan dalam format List Kartu Ringkas (Bullet Points •) yang rapi, elegan, ber-emoji, dan mudah dibaca di layar HP tanpa perlu geser layar:
     • **172.16.250.xx** | 🟢 ONLINE (atau ⚪ OFFLINE / ⚠️ ANOMALI)
       - User / Mesin: ...
       - Vendor / Device: ...
       - MAC / Ports: ...`;

        // Check if Google Key (starts with AIza...)
        const isGoogleKey = AI_API_KEY.startsWith('AIza');

        let replyText = '';

        if (isGoogleKey) {
            // Use native Google Gemini REST API with automatic model fallback
            let userModel = (AI_MODEL && AI_MODEL !== 'free-ai') ? AI_MODEL : 'gemini-3.6-flash';
            if (userModel === 'gemini-2.0-flash') {
                userModel = 'gemini-3.6-flash';
            }
            const fallbackModels = Array.from(new Set([
                userModel,
                'gemini-3.6-flash',
                'gemini-3.6-pro',
                'gemini-2.5-flash',
                'gemini-1.5-flash'
            ]));

            const contents = [
                {
                    role: 'user',
                    parts: [{ text: `${systemPrompt}\n\nPertanyaan User:\n${message.trim()}` }]
                }
            ];

            let lastError = null;

            for (const modelName of fallbackModels) {
                const geminiUrl = `https://generativelanguage.googleapis.com/v1beta/models/${modelName}:generateContent?key=${AI_API_KEY}`;

                try {
                    const geminiRes = await fetch(geminiUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ contents }),
                        signal: AbortSignal.timeout(45000)
                    });

                    if (geminiRes.ok) {
                        const geminiData = await geminiRes.json();
                        replyText = geminiData?.candidates?.[0]?.content?.parts?.[0]?.text || 'Analisis selesai.';
                        lastError = null;
                        break;
                    } else {
                        const errBody = await geminiRes.text();
                        lastError = `HTTP ${geminiRes.status}: ${errBody}`;
                        // If 503 / 429 / 404, try next model in fallback list
                        if (geminiRes.status === 503 || geminiRes.status === 429 || geminiRes.status === 404) {
                            console.warn(`Model ${modelName} returned ${geminiRes.status}, falling back...`);
                            continue;
                        }
                        break;
                    }
                } catch (e) {
                    lastError = e.message;
                }
            }

            if (lastError && !replyText) {
                return res.status(500).json({
                    error: `Gagal dari Google Gemini: ${lastError.substring(0, 300)}`
                });
            }
        } else {
            // Use custom / OpenCode / OpenAI-compatible endpoint
            let targetBaseUrl = AI_API_BASE_URL || 'https://api.openai.com/v1';
            if (!targetBaseUrl.startsWith('http://') && !targetBaseUrl.startsWith('https://')) {
                targetBaseUrl = `https://${targetBaseUrl}`;
            }

            const finalUrl = targetBaseUrl.endsWith('/chat/completions')
                ? targetBaseUrl
                : `${targetBaseUrl}/chat/completions`;

            const messages = [
                { role: 'system', content: systemPrompt },
                ...history.slice(-6).map((h) => ({ role: h.role, content: h.content })),
                { role: 'user', content: message.trim() }
            ];

            let preferredModel = AI_MODEL || 'mimo-v2.5-free';

            const fallbackOpenCodeModels = Array.from(new Set([
                preferredModel,
                'mimo-v2.5-free',
                'big-pickle'
            ]));

            let lastOpenCodeError = null;

            for (const modelName of fallbackOpenCodeModels) {
                try {
                    const aiResponse = await fetch(finalUrl, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${AI_API_KEY}`,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            model: modelName,
                            messages,
                            stream: false
                        }),
                        signal: AbortSignal.timeout(45000)
                    });

                    if (aiResponse.ok) {
                        const aiData = await aiResponse.json();
                        replyText = aiData?.choices?.[0]?.message?.content || 'Analisis selesai.';
                        lastOpenCodeError = null;
                        break;
                    } else {
                        const errBody = await aiResponse.text();
                        lastOpenCodeError = `HTTP ${aiResponse.status} (${modelName}): ${errBody}`;
                        console.warn(`OpenCode model ${modelName} returned ${aiResponse.status}, falling back to next model...`);
                        continue;
                    }
                } catch (fetchErr) {
                    lastOpenCodeError = fetchErr.message;
                    continue;
                }
            }

            if (lastOpenCodeError && !replyText) {
                return res.status(500).json({
                    error: `Gagal dari API (${finalUrl}): ${lastOpenCodeError.substring(0, 350)}`
                });
            }
        }

        return res.status(200).json({
            reply: replyText,
            timestamp: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
            summary: ipData ? ipData.summary : null,
            last_updated: ipData ? (ipData.last_updated_human || ipData.last_updated) : null
        });
    } catch (err) {
        console.error('Server error:', err);
        return res.status(500).json({
            error: `Terjadi kendala pada server: ${err.message}`
        });
    }
}
