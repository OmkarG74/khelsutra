import { spawn } from 'child_process';
import fs from 'fs';
import path from 'path';

const CHROME_PATH = "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe";
const PORT = 9222;
const USER_DATA = path.join(process.cwd(), 'scratch', 'chrome-profile-test');

if (!fs.existsSync(USER_DATA)) {
    fs.mkdirSync(USER_DATA, { recursive: true });
}

// 1. Launch Chrome Headless
const chromeProc = spawn(CHROME_PATH, [
    `--remote-debugging-port=${PORT}`,
    '--headless=new',
    '--disable-gpu',
    '--no-sandbox',
    '--disable-extensions',
    `--user-data-dir=${USER_DATA}`,
    'about:blank'
]);

// Wait 2 seconds for Chrome to start
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

async function run() {
    try {
        const wsUrl = await getWsUrl();
        const client = new CDPClient(wsUrl);

        // Enable domains
        await client.send('Page.enable');
        await client.send('Runtime.enable');
        await client.send('Emulation.setDeviceMetricsOverride', {
            width: 1920,
            height: 1080,
            deviceScaleFactor: 1,
            mobile: false
        });

        const pagesToTest = [
            {
                url: 'http://127.0.0.1:8000/athletes/20?success=Athlete+details+updated+successfully.',
                out: 'scratch/clean_athletes_20_details.png',
                name: 'Athlete 20 Details (Single Alert Verification)'
            },
            {
                url: 'http://127.0.0.1:8000/athletes/20/edit',
                out: 'scratch/clean_athletes_20_edit.png',
                name: 'Athlete 20 Edit'
            },
            {
                url: 'http://127.0.0.1:8000/athletes',
                out: 'scratch/clean_athletes_list.png',
                name: 'Athletes List'
            }
        ];

        for (const item of pagesToTest) {
            console.log(`Navigating to ${item.name}: ${item.url}`);
            await client.send('Page.navigate', { url: item.url });
            await new Promise(r => setTimeout(r, 2000));

            const shot = await client.send('Page.captureScreenshot', { format: 'png' });
            fs.writeFileSync(path.join(process.cwd(), item.out), Buffer.from(shot.data, 'base64'));
            console.log(`Saved screenshot to ${item.out}`);
        }

        // --- TEST B: Open /athletes/create, test dynamic documents button ---
        console.log('\n--- Running TEST B on /athletes/create ---');
        await client.send('Page.navigate', { url: 'http://127.0.0.1:8000/athletes/create' });
        await new Promise(r => setTimeout(r, 2000));

        // Evaluate Document Buttons in DOM
        const docButtonsEval = await client.send('Runtime.evaluate', {
            expression: `(() => {
                const buttons = Array.from(document.querySelectorAll('button')).filter(b => b.textContent.includes('Add Document') || b.textContent.includes('Add Another Document'));
                return {
                    count: buttons.length,
                    texts: buttons.map(b => b.textContent.trim())
                };
            })()`,
            returnByValue: true
        });

        console.log("Document buttons found in Create view:", JSON.stringify(docButtonsEval.result.value));

        // Click "+ Add Document" once
        console.log("Clicking '+ Add Document' once...");
        await client.send('Runtime.evaluate', {
            expression: `(() => {
                addDocumentRow();
                return document.getElementById('documentsContainer').children.length;
            })()`,
            returnByValue: true
        });

        let rowsCount = (await client.send('Runtime.evaluate', {
            expression: `document.getElementById('documentsContainer').children.length`,
            returnByValue: true
        })).result.value;
        console.log(`Rows after 1st click: ${rowsCount} (Expected: 1)`);

        // Click "+ Add Document" a second time
        console.log("Clicking '+ Add Document' second time...");
        await client.send('Runtime.evaluate', {
            expression: `(() => {
                addDocumentRow();
                return document.getElementById('documentsContainer').children.length;
            })()`,
            returnByValue: true
        });

        rowsCount = (await client.send('Runtime.evaluate', {
            expression: `document.getElementById('documentsContainer').children.length`,
            returnByValue: true
        })).result.value;
        console.log(`Rows after 2nd click: ${rowsCount} (Expected: 2)`);

        // Scroll down to documents section and capture screenshot
        await client.send('Runtime.evaluate', {
            expression: `document.querySelector('.bi-file-earmark-arrow-up-fill').scrollIntoView({behavior: 'instant', block: 'center'})`
        });
        await new Promise(r => setTimeout(r, 1000));

        const createShot = await client.send('Page.captureScreenshot', { format: 'png' });
        fs.writeFileSync(path.join(process.cwd(), 'scratch/clean_athletes_create_documents.png'), Buffer.from(createShot.data, 'base64'));
        console.log('Saved screenshot of create athlete documents to scratch/clean_athletes_create_documents.png');

        // Capture full top of create athlete
        await client.send('Runtime.evaluate', {
            expression: `window.scrollTo(0, 0)`
        });
        await new Promise(r => setTimeout(r, 1000));

        const createTopShot = await client.send('Page.captureScreenshot', { format: 'png' });
        fs.writeFileSync(path.join(process.cwd(), 'scratch/clean_athletes_create_top.png'), Buffer.from(createTopShot.data, 'base64'));
        console.log('Saved screenshot of create athlete top to scratch/clean_athletes_create_top.png');

        await client.close();
    } finally {
        chromeProc.kill();
    }
}

run().then(() => {
    console.log('Finished browser tests!');
    process.exit(0);
}).catch(err => {
    console.error('Error in browser test:', err);
    chromeProc.kill();
    process.exit(1);
});
