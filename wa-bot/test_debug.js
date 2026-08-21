import {
    makeWASocket,
    useMultiFileAuthState,
    Browsers
} from '@whiskeysockets/baileys';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const authDir = path.join(__dirname, 'wa_auth');

setInterval(() => {}, 10000);

const { state, saveCreds } = await useMultiFileAuthState(authDir);

const sock = makeWASocket({
    auth: state,
    browser: Browsers.windows('Desktop'),
});

sock.ev.on('creds.update', saveCreds);

sock.ev.on('connection.update', (update) => {
    console.log('UPDATE RAW:', JSON.stringify(update));
});
