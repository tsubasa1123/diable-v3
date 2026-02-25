const express = require('express');
const bodyParser = require('body-parser');

const app = express();
const PORT = 3000;

// Middleware
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

// Servir des fichiers statiques
app.use(express.static('public'));

// Configuration
const VICTIM_SERVER = process.env.VICTIM_SERVER || 'http://victim-server:8080';
const MITM_PROXY = process.env.MITM_PROXY || 'http://mitm-proxy:8888';

// Endpoint pour obtenir la configuration
app.get('/api/config', (req, res) => {
  res.json({
    success: true,
    config: {
      victimServer: VICTIM_SERVER,
      mitmProxy: MITM_PROXY,
      labPort: PORT
    }
  });
});

// Health check endpoint (DIABLE standard)
app.get('/health', (req, res) => {
  const healthCheck = {
    status: 'healthy',
    service: 'mitm-attack-lab',
    timestamp: new Date().toISOString(),
    checks: {
      application: {
        status: 'ok',
        uptime: process.uptime()
      },
      victimServer: VICTIM_SERVER,
      mitmProxy: MITM_PROXY
    }
  };

  res.status(200).json(healthCheck);
});

// Reset endpoint (DIABLE standard)
app.post('/reset', async (req, res) => {
  try {
    // Ici on pourrait reset les données si nécessaire
    res.json({
      success: true,
      message: 'Lab reset successfully',
      timestamp: new Date().toISOString()
    });
  } catch (err) {
    res.status(500).json({
      success: false,
      message: 'Reset failed',
      error: err.message
    });
  }
});

app.listen(PORT, () => {
  console.log(`
╔═══════════════════════════════════════════════════════╗
║  🔓 MITM Attack Lab - DIABLE v3.0                     ║
╠═══════════════════════════════════════════════════════╣
║  Lab Interface: http://localhost:${PORT}                ║
║  Victim Server: ${VICTIM_SERVER}              ║
║  MITM Proxy: ${MITM_PROXY}                 ║
╠═══════════════════════════════════════════════════════╣
║  ⚠️  Educational Lab - DO NOT USE IN PRODUCTION!       ║
╚═══════════════════════════════════════════════════════╝
  `);
});
