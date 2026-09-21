import { spawn } from 'child_process';
import fs from 'fs';
import path from 'path';

const CHROME_PATH = "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe";
const PORT = 9223;
const USER_DATA = path.join(process.cwd(), 'scratch', 'chrome-profile-test-23');

if (!fs.existsSync(USER_DATA)) {
    fs.mkdirSync(USER_DATA, { recursive: true });
}

const chromeProc = spawn(CHROME_PATH, [
    `--remote-debugging-port=${PORT}`,
    '--headless=new',
    '--disable-gpu',
    '--no-sandbox',
    '--disable-extensions',
    `--user-data-dir=${USER_DATA}`,
    'about:blank'
]);

await new Promise(r => setTimeout(r, 2000));

async function getWsUrl() {
    const res = await fetch(`http://127.0.0.1:${PORT}/json/list`);
    const data = await res.json();
    const page = data.find(t => t.type === 'page');
    return page ? page.webSocketDebuggerUrl : data[0].webSocketDebuggerUrl;
}

class CDPClient {
    constructor(wsUrl) {
        this.ws = new WebSocket(wsUrl);
        this.id = 1;
        this.callbacks = new Map();
        this.ready = new Promise((resolve, reject) => {
            this.ws.onopen = resolve;
            this.ws.onerror = reject;
        });
        this.ws.onmessage = (event) => {
            const msg = JSON.parse(event.data);
            if (msg.id && this.callbacks.has(msg.id)) {
                const cb = this.callbacks.get(msg.id);
                this.callbacks.delete(msg.id);
                if (msg.error) cb.reject(msg.error);
                else cb.resolve(msg.result);
            }
        };
    }

    async send(method, params = {}) {
        await this.ready;
        const id = this.id++;
        return new Promise((resolve, reject) => {
            this.callbacks.set(id, { resolve, reject });
            this.ws.send(JSON.stringify({ id, method, params }));
        });
    }

    async close() {
        this.ws.close();
    }
}

try {
    const wsUrl = await getWsUrl();
    const client = new CDPClient(wsUrl);
    await client.send('Page.enable');
    await client.send('Emulation.setDeviceMetricsOverride', {
        width: 1920,
        height: 1080,
        deviceScaleFactor: 1,
        mobile: false
    });

    await client.send('Page.navigate', { url: 'http://127.0.0.1:8000/athletes/23' });
    await new Promise(r => setTimeout(r, 2000));

    const shot = await client.send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(process.cwd(), 'scratch/clean_athletes_23_details.png'), Buffer.from(shot.data, 'base64'));
    console.log('Saved scratch/clean_athletes_23_details.png');
    await client.close();
} finally {
    chromeProc.kill();
}
