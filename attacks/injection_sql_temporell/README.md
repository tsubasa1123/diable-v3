# ⚡ SQLi Time-Based Lab

> Démonstration interactive des attaques **Time-Based Blind SQL Injection**  
> À des fins **éducatives uniquement** — OWASP A03:2021 / CWE-89

---

## 📁 Structure du projet

```
sqli_lab/
├── app.py               ← Backend Flask (endpoints vulnérable & sécurisé)
├── templates/
│   └── index.html       ← Interface web (HTML/CSS/JS)
├── requirements.txt
├── Dockerfile
└── docker-compose.yml
```

---

## 🚀 Lancement rapide

### Avec Docker Compose (recommandé)

```bash
# Cloner / décompresser le projet, puis :
cd sqli_lab

docker compose up --build
```

Ouvrir → **http://localhost:5000**

### Manuellement (Python)

```bash
pip install -r requirements.txt
python app.py
```

---

## 🔌 API Endpoints

| Méthode | Route                    | Description                              |
|---------|--------------------------|------------------------------------------|
| GET     | `/`                      | Interface web                            |
| GET     | `/api/stats`             | Statistiques base de données             |
| POST    | `/api/login/vulnerable`  | ❌ Login vulnérable (injection directe)  |
| POST    | `/api/login/secure`      | ✅ Login sécurisé (requête paramétrée)   |
| POST    | `/api/timebased`         | 🕐 Injection temporelle personnalisée    |
| GET     | `/api/payloads`          | Liste des payloads de démonstration      |

### Exemple de payload time-based

```json
POST /api/login/vulnerable
{
  "username": "admin' AND (SELECT CASE WHEN (SELECT COUNT(*) FROM users WHERE role='admin')>0 THEN (SELECT SUM(s.id) FROM secrets s,users u,users u2) ELSE 1 END)>0--",
  "password": ""
}
```

---

## 🛡️ Contre-mesures démontrées

| Technique         | Explication                                          |
|-------------------|------------------------------------------------------|
| Requêtes paramétrées | `cursor.execute(sql, (param,))` — données ≠ code  |
| ORM               | SQLAlchemy paramètre automatiquement                 |
| Validation        | Whitelist, longueur max, types stricts               |
| WAF               | ModSecurity / Cloudflare bloquent les patterns SQLi  |
| Monitoring        | Alertes sur anomalies de latence SQL                 |

---

## ⚠️ Avertissement légal

Ce projet est destiné **exclusivement à l'apprentissage** de la cybersécurité  
dans un environnement contrôlé. Toute utilisation sur des systèmes non autorisés  
est **illégale et répréhensible**.