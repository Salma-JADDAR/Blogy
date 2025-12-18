# 📘 README – Projet **BlogCMS**

## 📌 Contexte du Projet

BlogCMS souhaite disposer d’une interface complète permettant à ses clients de gérer facilement leur blog au quotidien. Après la validation du schéma de base de données, votre mission consiste à développer :

* Le **backend** du système
* Le **tableau de bord administrateur**
* Les fonctionnalités pour chaque type d’utilisateur

Ce document décrit les fonctionnalités, technologies et étapes pour mettre en place le projet.

---

## 🎯 Fonctionnalités Requises

### 🔐 Pour Tous les Utilisateurs

* Page de **login sécurisée**
* **Système de rôles** : admin, éditeur, utilisateur

### 🛠️ Pour les Administrateurs

* Dashboard avec **statistiques globales**
* CRUD complet des **catégories**
* **Modération des commentaires**
* **Gestion des utilisateurs**

### ✍️ Pour les Auteurs

* Voir leurs **articles publiés**
* **Créer**, **éditer**, **supprimer** leurs propres articles
* Poster des commentaires

### 👀 Pour les Visiteurs

* Voir les **articles publiés**
* Poster des commentaires

### ⭐ Bonus

* Upload d’**images**
* Fonction de **recherche** des articles
* **Pagination** des listes

---

## 🧰 Technologies Obligatoires

### 🔧 Backend

* **PHP 8 (procédural)**
* **MySQL ou PostgreSQL**
* **PDO** + requêtes préparées

### 🎨 Frontend

* HTML5 / CSS3
* **TailwindCSS** ou **Bootstrap**
* JavaScript basique (validation + interactions)

### 🛡️ Sécurité

* Sessions PHP **sécurisées**
* Mot de passe hashé via **bcrypt** (`password_hash()`)
* Protection **XSS** avec `htmlspecialchars()`
* Validation stricte des formulaires

-
---

## 📝 Fichier de Connexion PDO (exemple)

```php
<?php
$host = "localhost";
$dbname = "blogcms";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
```

---

## 🚀 Étapes de Développement

1. **Créer la structure du projet**
2. Configurer **PDO + base de données**
3. Implémenter la **page de login** + système de rôles
4. Développer le **dashboard admin**
5. Ajouter le CRUD des **catégories**
6. Ajouter le CRUD des **articles**
7. Gestion des **commentaires**
8. Interface **visiteur + auteur**
9. Ajouter les fonctionnalités **bonus**
10. Tests + sécurisation

---

## 📄 Auteur

Projet développé pour **BlogCMS** dans le cadre d’un exercice pratique d’application Backend/Frontend.
