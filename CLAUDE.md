# Projet : Dashboard de suivi de commandes en temps réel

## Contexte

Projet portfolio développé par Anna Maetz (Full Stack Developer, 10+ ans d'expérience PHP/JS/MySQL) dans le cadre d'une recherche d'emploi de développeuse web généraliste dans la région de Strasbourg. Le projet sert à démontrer des compétences PHP modernes + API REST + temps réel pour compléter un profil jusque-là plutôt orienté supervision industrielle.

## Objectif du projet

Une appli de suivi de commandes/livraisons en temps réel, inspirée du tracking de colis. Un visiteur suit une commande via un code de tracking (sans compte), et voit son statut se mettre à jour en direct (sans rafraîchir la page). Côté admin, on peut créer des commandes et changer leur statut.

## Stack technique

- **Backend** : Symfony 7 (PHP 8.2+)
- **Base de données** : MySQL via XAMPP (dev local, Windows)
- **ORM** : Doctrine
- **Temps réel** : Mercure (à venir — pas encore implémenté)
- **Auth admin** : à définir (JWT prévu, pas encore implémenté)
- **Front** : HTML/CSS/JS vanilla prévu (pas de framework front, pour rester dans un profil "généraliste PHP" plutôt que JS-heavy)
- **Versionning** : Git + GitHub (repo : `AnnaMaetz/suivi-commandes-symfony`)

## Environnement de dev

- Windows, XAMPP (PHP + MySQL, Apache démarré seulement pour phpMyAdmin)
- VS Code + terminal intégré PowerShell
- Symfony CLI installé, `symfony server:start` pour lancer le serveur local
- Base de données locale : `suivi_commandes`
- `.env` : `DATABASE_URL="mysql://root:@127.0.0.1:3306/suivi_commandes?serverVersion=10.4.32-MariaDB"` (⚠️ XAMPP fournit MariaDB, pas MySQL — bien indiquer `serverVersion=...-MariaDB` sinon Doctrine se trompe de moteur)

## État d'avancement actuel

✅ Projet Symfony 7 créé et fonctionnel (`symfony server:start` marche)
✅ Connecté à GitHub, historique de commits en place
✅ Base de données `suivi_commandes` créée
✅ Entité `CustomerOrder` créée (⚠️ pas `Order`, car mot réservé SQL) avec les champs :
   - `trackingCode` (string, 12, unique)
   - `customerName` (string, 100)
   - `status` (string, 20 — actuellement stocké en string simple, pas encore migré vers l'enum PHP prévu)
   - `createdAt` / `updatedAt` (datetime_immutable)
✅ Entité `OrderStatusHistory` créée avec :
   - `status` (string, 20)
   - `note` (string, 255, nullable)
   - `changedAt` (datetime_immutable)
   - Relation `ManyToOne` vers `CustomerOrder`
✅ Migration générée et appliquée (tables `customer_order` et `order_status_history` existent en BDD)
✅ Contrôleur `src/Controller/Api/OrderController.php` créé avec un premier endpoint :
   - `GET /api/orders/{trackingCode}` → retourne les infos d'une commande ou une erreur 404

## Prochaines étapes prévues (dans l'ordre)

1. **Enum PHP `OrderStatus`** — remplacer le champ `status` (string libre) par un enum PHP typé (`src/Enum/OrderStatus.php`) avec les valeurs : `created`, `preparing`, `shipped`, `out_for_delivery`, `delivered`, chacune avec un `label()` en français
2. **Endpoint de création** — `POST /api/orders` (créer une commande, génère un tracking code aléatoire)
3. **Endpoint de mise à jour de statut** — `PATCH /api/orders/{id}/status` (change le statut + ajoute une entrée dans `order_status_history`)
4. **Mercure** — pousser un event `order.status_updated` à chaque changement de statut, pour le temps réel
5. **Auth JWT** — protéger les routes admin (création, changement de statut) avec LexikJWTAuthenticationBundle
6. **Front minimal** — page de tracking public en JS vanilla, abonnement à Mercure via EventSource
7. **Tests** — PHPUnit + Symfony test client sur les endpoints
8. **README + déploiement** — documentation claire (le projet doit être présentable à des recruteurs), déploiement Docker ou sur une plateforme gratuite (Railway/Render)

## Conventions de travail

- Niveau : Anna est débutante sur Symfony spécifiquement (mais expérimentée en PHP général), donc préférer des explications claires, éviter le jargon non expliqué, proposer des commandes testables à chaque étape.
- Commits : un commit par étape logique, messages clairs en anglais (ex: "Add Order and OrderStatusHistory entities") pour un historique GitHub lisible par des recruteurs.
- Le projet doit rester présentable comme pièce de portfolio : code propre, README soigné, pas de raccourcis qui nuiraient à la lisibilité.
