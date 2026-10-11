/**
 * Simple Static Web Server for StudentHub Portal
 * Enables Fetch API testing without browser CORS/file:// protocol restrictions.
 * Usage: node server.js
 */

const http = require('http');
const fs = require('fs');
const path = require('path');

const PORT = 5000;
const PROJECT_DIR = path.join(__dirname, '..');
const FRONTEND_DIR = path.join(PROJECT_DIR, 'frontend');

const MIME_TYPES = {
    '.html': 'text/html; charset=utf-8',
    '.css': 'text/css; charset=utf-8',
    '.js': 'application/javascript; charset=utf-8',
    '.json': 'application/json; charset=utf-8',
    '.png': 'image/png',
    '.jpg': 'image/jpeg',
    '.jpeg': 'image/jpeg',
    '.svg': 'image/svg+xml',
    '.ico': 'image/x-icon'
};

const server = http.createServer((req, res) => {
    let reqUrl;
    try {
        reqUrl = decodeURI(new URL(req.url, 'http://localhost').pathname);
    } catch {
        res.writeHead(400, { 'Content-Type': 'text/plain; charset=utf-8' });
        res.end('400 Bad Request');
        return;
    }
    if (reqUrl === '/') reqUrl = '/index.html';
    if (reqUrl.startsWith('/backend/')) {
        res.writeHead(503, { 'Content-Type': 'application/json; charset=utf-8' });
        res.end(JSON.stringify({ message: 'Run StudentHub with XAMPP Apache and MySQL to use the PHP database API.' }));
        return;
    }

    const publicData = /^\/database\/data\/(faqs|timetable)\.json$/.test(reqUrl);
    const baseDir = publicData ? PROJECT_DIR : FRONTEND_DIR;
    const relativePath = publicData ? reqUrl.slice(1) : reqUrl.replace(/^\/+/, '');
    const filePath = path.resolve(baseDir, relativePath);
    if (!filePath.startsWith(baseDir + path.sep)) {
        res.writeHead(403, { 'Content-Type': 'text/plain; charset=utf-8' });
        res.end('403 Forbidden');
        return;
    }

    fs.stat(filePath, (err, stats) => {
        if (err || !stats.isFile()) {
            res.writeHead(404, { 'Content-Type': 'text/plain' });
            res.end('404 Not Found: ' + reqUrl);
            return;
        }

        const ext = path.extname(filePath).toLowerCase();
        const contentType = MIME_TYPES[ext] || 'application/octet-stream';

        res.writeHead(200, { 'Content-Type': contentType });
        fs.createReadStream(filePath).pipe(res);
    });
});

server.listen(PORT, () => {
    console.log(`StudentHub static preview running at http://localhost:${PORT}/`);
    console.log('Use XAMPP Apache and MySQL for registration, login and events.');
});
