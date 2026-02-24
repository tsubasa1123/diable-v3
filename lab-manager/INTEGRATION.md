# 🔌 Guide d'intégration — Lab Manager API

> Ce document est destiné au développeur du site d'apprentissage.  
> Il explique comment connecter le site aux labs de sécurité.

---

## Vue d'ensemble

```
Site d'apprentissage          Lab Manager (cette API)        Docker
        |                              |                        |
        |  1. POST /api/spawn  ──────> |                        |
        |                              |  docker run ────────>  |
        |  2. { url, expires_in } <──  |                        |
        |                              |                        |
        |  3. Redirige l'apprenant     |                        |
        |     vers l'URL du lab        |                        |
```

L'apprenant clique "Lancer le lab" → le site appelle l'API → l'API démarre un conteneur Docker → l'apprenant est redirigé vers son lab personnel.

---

## Configuration

Ajouter dans le `.env` du site d'apprentissage :

```env
LAB_MANAGER_URL=http://IP_DU_VPS:3000
LAB_MANAGER_SECRET=la_cle_api_partagee   # Fournie par l'admin du VPS
```

---

## Les 4 endpoints

### `GET /api/health` — Vérifier que le service tourne
Pas d'authentification requise.

```js
const res  = await fetch(`${LAB_MANAGER_URL}/api/health`);
const data = await res.json();
// { "status": "ok", "timestamp": "2025-01-15T10:30:00.000Z" }
```

---

### `GET /api/labs` — Liste des labs disponibles
Pas d'authentification requise. Utile pour afficher le catalogue.

```js
const res  = await fetch(`${LAB_MANAGER_URL}/api/labs`);
const data = await res.json();
// {
//   "labs": [
//     { "id": "xss",   "title": "Cross-Site Scripting (XSS)", "exercises": 4 },
//     { "id": "sqli",  "title": "SQL Injection",               "exercises": 4 },
//     { "id": "xpath", "title": "XPath Injection",             "exercises": 3 },
//     { "id": "csrf",  "title": "CSRF",                        "exercises": 3 },
//     { "id": "cmdinjection", "title": "Command Injection",    "exercises": 3 }
//   ]
// }
```

---

### `POST /api/spawn` — Démarrer un lab ⭐
Header requis : `X-API-Key`

```js
async function startLab(userId, labId) {
    const res = await fetch(`${LAB_MANAGER_URL}/api/spawn`, {
        method:  'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-API-Key':    LAB_MANAGER_SECRET,
        },
        body: JSON.stringify({
            user_id: String(userId),  // ID de l'utilisateur connecté
            lab:     labId,           // 'xss' | 'sqli' | 'xpath' | 'csrf' | 'cmdinjection'
        }),
    });

    if (!res.ok) {
        const err = await res.json();
        throw new Error(err.error);
    }

    return await res.json();
    // {
    //   "url":        "http://IP:8051",  ← ouvrir dans un nouvel onglet
    //   "expires_in": 2700,              ← secondes avant expiration (45 min)
    //   "status":     "running",
    //   "reused":     false              ← true si le conteneur existait déjà
    // }
}

// Utilisation dans un bouton "Lancer le lab"
const { url, expires_in } = await startLab(currentUser.id, 'xss');
window.open(url, '_blank');  // Ouvrir dans un nouvel onglet
```

---

### `POST /api/destroy` — Arrêter un lab
Header requis : `X-API-Key`  
Appeler quand l'apprenant clique "Terminer le lab" ou quand il se déconnecte.

```js
async function stopLab(userId, labId) {
    await fetch(`${LAB_MANAGER_URL}/api/destroy`, {
        method:  'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-API-Key':    LAB_MANAGER_SECRET,
        },
        body: JSON.stringify({ user_id: String(userId), lab: labId }),
    });
}
```

---

### `GET /api/status` — Vérifier si un lab tourne
Header requis : `X-API-Key`  
Utile pour afficher "Reprendre le lab" si l'apprenant revient sur la page.

```js
async function getLabStatus(userId, labId) {
    const res = await fetch(
        `${LAB_MANAGER_URL}/api/status?user_id=${userId}&lab=${labId}`,
        { headers: { 'X-API-Key': LAB_MANAGER_SECRET } }
    );
    return await res.json();
    // Si actif  : { "running": true,  "url": "http://IP:8051", "expires_in": 1500 }
    // Si arrêté : { "running": false }
}
```

---

## Exemple complet — Bouton "Lancer le lab"

```jsx
// React — Composant bouton avec gestion des états
function LaunchLabButton({ userId, labId }) {
    const [status, setStatus] = useState('idle'); // idle | loading | running | error
    const [labUrl, setLabUrl] = useState(null);
    const [expiresIn, setExpiresIn] = useState(null);

    // Vérifier si un lab tourne déjà au chargement
    useEffect(() => {
        getLabStatus(userId, labId).then(data => {
            if (data.running) {
                setStatus('running');
                setLabUrl(data.url);
                setExpiresIn(data.expires_in);
            }
        });
    }, []);

    async function handleLaunch() {
        setStatus('loading');
        try {
            const data = await startLab(userId, labId);
            setStatus('running');
            setLabUrl(data.url);
            setExpiresIn(data.expires_in);
            window.open(data.url, '_blank');
        } catch (err) {
            setStatus('error');
        }
    }

    async function handleStop() {
        await stopLab(userId, labId);
        setStatus('idle');
        setLabUrl(null);
    }

    if (status === 'loading') return <button disabled>⏳ Démarrage en cours...</button>;

    if (status === 'running') return (
        <div>
            <a href={labUrl} target="_blank">🚀 Ouvrir le lab</a>
            <span>⏱ Expire dans {Math.round(expiresIn / 60)} min</span>
            <button onClick={handleStop}>⏹ Terminer</button>
        </div>
    );

    if (status === 'error') return <button onClick={handleLaunch}>❌ Erreur — Réessayer</button>;

    return <button onClick={handleLaunch}>🚀 Lancer le lab</button>;
}
```

---

## Gestion des erreurs

| Code HTTP | Signification | Action recommandée |
|---|---|---|
| `200` | OK | Utiliser la réponse |
| `400` | Paramètre manquant ou lab inconnu | Vérifier `user_id` et `lab` |
| `401` | Header `X-API-Key` manquant | Ajouter le header |
| `403` | Clé API incorrecte | Vérifier `LAB_MANAGER_SECRET` |
| `500` | Erreur Docker | Vérifier les logs du Lab Manager |

---

## Notes importantes

- Les conteneurs sont **détruits automatiquement** après 45 min d'inactivité — prévoir un avertissement dans l'UI.
- Un apprenant **ne peut avoir qu'un seul conteneur actif par lab** — appeler `/spawn` sur un lab déjà actif renouvelle le TTL sans créer de doublon.
- Le lab est **anonyme** — il ne connaît pas l'identité de l'apprenant.
- L'URL du lab s'ouvre dans un **nouvel onglet** — ne pas intégrer en iframe (les labs XSS et CSRF peuvent poser des problèmes de sécurité navigateur).
