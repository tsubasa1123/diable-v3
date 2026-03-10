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
        |  2. { url, urls, expires_in} |                        |
        |     <─────────────────────── |                        |
        |                              |                        |
        |  3. Redirige l'apprenant     |                        |
        |     vers l'URL du lab        |                        |
```

L'apprenant clique "Lancer le lab" → le site appelle l'API → l'API démarre un ou plusieurs conteneurs Docker → l'apprenant est redirigé vers son lab personnel.

---

## Configuration

Ajouter dans le `.env` du site d'apprentissage :

```env
LAB_MANAGER_URL=http://IP_DU_VPS:4000
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
//     { "id": "xss",              "title": "Cross-Site Scripting (XSS)",       "exercises": 4 },
//     { "id": "sqli-comments",    "title": "SQL Injection - Comments",          "exercises": 4 },
//     { "id": "sqli-error-based", "title": "SQL Injection - Error Based",       "exercises": 4 },
//     { "id": "xpath-injection",  "title": "XPath Injection",                   "exercises": 3 },
//     { "id": "nosql-injection",  "title": "NoSQL Injection",                   "exercises": 3 },
//     { "id": "csrf",             "title": "CSRF",                              "exercises": 3 },
//     { "id": "shellshock",       "title": "Shellshock (CVE-2014-6271)",        "exercises": 3 },
//     { "id": "mfa-bypass",       "title": "MFA Bypass",                        "exercises": 3 },
//     { "id": "log4shell",        "title": "Log4Shell (CVE-2021-44228)",        "exercises": 3 },
//     { "id": "mitm-attack",      "title": "MITM Attack",                       "exercises": 3 },
//     { "id": "nosql-injection",  "title": "NoSQL Injection",                   "exercises": 3 },
//     { "id": "n8n",              "title": "n8n Workflow Automation",           "exercises": 3 }
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
            lab:     labId,           // ex: 'xss', 'sqli-comments', 'mitm-attack'...
        }),
    });

    if (!res.ok) {
        const err = await res.json();
        throw new Error(err.error);
    }

    return await res.json();
}
```

#### Réponse — lab à un seul conteneur (cas standard)

```json
{
  "url":        "http://IP:8051",
  "urls": {
    "main":     "http://IP:8051"
  },
  "expires_in": 2700,
  "status":     "running",
  "reused":     false
}
```

#### Réponse — lab multi-conteneurs avec plusieurs URLs (ex: MITM Attack)

```json
{
  "url":        "http://IP:8071",
  "urls": {
    "main":     "http://IP:8071",
    "victim":   "http://IP:8072"
  },
  "expires_in": 2700,
  "status":     "running",
  "reused":     false
}
```

> `url` est toujours présent pour la rétrocompatibilité — c'est toujours l'URL principale.  
> `urls` contient toutes les URLs du lab (y compris `main` qui est identique à `url`).

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
// React — Composant bouton avec gestion des états et multi-URLs
function LaunchLabButton({ userId, labId }) {
    const [status,    setStatus]    = useState('idle'); // idle | loading | running | error
    const [labUrls,   setLabUrls]   = useState(null);   // { main, victim?, ... }
    const [expiresIn, setExpiresIn] = useState(null);

    // Vérifier si un lab tourne déjà au chargement
    useEffect(() => {
        getLabStatus(userId, labId).then(data => {
            if (data.running) {
                setStatus('running');
                setLabUrls({ main: data.url });
                setExpiresIn(data.expires_in);
            }
        });
    }, []);

    async function handleLaunch() {
        setStatus('loading');
        try {
            const data = await startLab(userId, labId);
            setStatus('running');
            setLabUrls(data.urls);
            setExpiresIn(data.expires_in);
            window.open(data.url, '_blank');  // Ouvrir l'URL principale
        } catch (err) {
            setStatus('error');
        }
    }

    async function handleStop() {
        await stopLab(userId, labId);
        setStatus('idle');
        setLabUrls(null);
    }

    if (status === 'loading') {
        return <button disabled>⏳ Démarrage en cours...</button>;
    }

    if (status === 'running') {
        return (
            <div>
                {/* URL principale — toujours présente */}
                <a href={labUrls.main} target="_blank">🚀 Ouvrir le lab</a>

                {/* URLs supplémentaires — uniquement si le lab en a */}
                {labUrls.victim && (
                    <a href={labUrls.victim} target="_blank">🎯 Ouvrir la victime</a>
                )}

                <span>⏱ Expire dans {Math.round(expiresIn / 60)} min</span>
                <button onClick={handleStop}>⏹ Terminer</button>
            </div>
        );
    }

    if (status === 'error') {
        return <button onClick={handleLaunch}>❌ Erreur — Réessayer</button>;
    }

    return <button onClick={handleLaunch}>🚀 Lancer le lab</button>;
}
```

---

## Labs avec plusieurs URLs

Certains labs exposent plusieurs interfaces. Voici la liste complète :

| Lab | `urls.main` | URLs supplémentaires |
|---|---|---|
| Tous les labs simples | Interface principale | — |
| `mitm-attack` | Interface du lab | `urls.victim` → serveur victime |

Pour savoir si un lab a des URLs supplémentaires, vérifier si `urls` contient des clés autres que `main` :

```js
const extraUrls = Object.entries(data.urls).filter(([key]) => key !== 'main');
// extraUrls = [["victim", "http://IP:8072"]] pour mitm-attack
// extraUrls = [] pour tous les autres labs
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

- Les conteneurs sont **détruits automatiquement** après 45 min d'inactivité — prévoir un avertissement dans l'UI avec un compte à rebours.
- Un apprenant **ne peut avoir qu'un seul conteneur actif par lab** — appeler `/spawn` sur un lab déjà actif renouvelle le TTL sans créer de doublon (`reused: true`).
- Le lab est **anonyme** — il ne connaît pas l'identité de l'apprenant.
- Les URLs des labs s'ouvrent dans un **nouvel onglet** — ne pas intégrer en iframe (les labs XSS et CSRF peuvent poser des problèmes de sécurité navigateur).
- Le champ `url` est toujours présent dans la réponse pour la rétrocompatibilité — utiliser `urls.main` pour les nouveaux développements.
