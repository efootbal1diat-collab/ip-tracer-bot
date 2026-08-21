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
    const AI_API_BASE_URL = (process.env.AI_API_BASE_URL || 'https://generativelanguage.googleapis.com/v1beta/openai').replace(/\/+$/, '');
    const AI_API_KEY = process.env.AI_API_KEY || '';
    const AI_MODEL = process.env.AI_MODEL || 'gemini-1.5-flash';

    try {
        // 1. Fetch latest IP data snapshot from GitHub
        let ipData = null;
        try {
            const rawUrl = `https://raw.githubusercontent.com/${GITHUB_REPO}/main/ip_data.json`;
            const headers = { 'User-Agent': 'IP-Tracer-Vercel-AI' };
            if (GITHUB_TOKEN) {
                headers['Authorization'] = `token ${GITHUB_TOKEN}`;
            }

            const fetchRes = await fetch(rawUrl, { headers });
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
                    const status = r.is_online ? 'ONLINE' : 'OFFLINE';
                    const user = r.excel_user ? `User:${r.excel_user}` : 'User:Kosong';
                    const machine = r.excel_machine ? `Machine:${r.excel_machine}` : '';
                    const vendor = r.vendor ? `Vendor:${r.vendor}` : '';
                    const free = r.is_free_ip ? '[BEBAS/KOSONG]' : '';
                    return `${r.full_ip} | ${status} | ${user} | ${machine} | ${vendor} | ${free}`.replace(/\s+\|\s+$/g, '');
                })
                .join('\n');
        }

        const systemPrompt = `Anda adalah AI Network Copilot pribadi untuk jaringan IT perusahaan.
Anda melayani administrator jaringan yang sedang mengakses melalui HP di luar kantor.
Berikut adalah data snapshot jaringan IP kantor terbaru yang disinkronkan dari database inventaris Excel dan scanner jaringan:

=== RINGKASAN JARINGAN ===
${summaryStr}

=== DAFTAR DATA IP (1-254) ===
${ipRecordsStr}

Instruksi Tugas:
1. Jawab pertanyaan user mengenai status IP, nama pemegang/user, mesin/device, merk vendor, port, atau alokasi IP kosong dengan akurat berdasarkan data di atas.
2. Jika user meminta IP kosong, prioritaskan memberikan IP yang bertanda [BEBAS/KOSONG] (status offline dan belum terdaftar di inventaris).
3. Buat jawaban yang terstruktur rapi, elegan, gunakan format Markdown (tabel jika relevan, bullet points •, teks tebal), dan ramah dibaca di layar HP.`;

        const messages = [
            { role: 'system', content: systemPrompt },
            ...history.slice(-6).map((h) => ({ role: h.role, content: h.content })),
            { role: 'user', content: message.trim() }
        ];

        // Call LLM API (Google AI Studio / OpenAI / OmniRoute)
        const aiResponse = await fetch(`${AI_API_BASE_URL}/chat/completions`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${AI_API_KEY}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                model: AI_MODEL,
                messages,
                stream: false
            })
        });

        if (!aiResponse.ok) {
            const errBody = await aiResponse.text();
            console.error('AI API Error:', errBody);
            return res.status(500).json({
                error: `Gagal memanggil AI (HTTP ${aiResponse.status}). Pastikan AI_API_KEY dan AI_API_BASE_URL sudah benar di Vercel.`,
                raw: errBody
            });
        }

        const aiData = await aiResponse.json();
        const replyText = aiData?.choices?.[0]?.message?.content || 'Analisis selesai tanpa pesan tambahan.';

        return res.status(200).json({
            reply: replyText,
            timestamp: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
            summary: ipData ? ipData.summary : null,
            last_updated: ipData ? (ipData.last_updated_human || ipData.last_updated) : null
        });
    } catch (err) {
        console.error('Server error:', err);
        return res.status(500).json({
            error: 'Terjadi kendala pada server: ' + err.message
        });
    }
}
